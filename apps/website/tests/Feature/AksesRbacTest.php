<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Filament\Pages\ManageContact;
use App\Filament\Resources\Achievements\AchievementResource;
use App\Filament\Resources\Albums\AlbumResource;
use App\Filament\Resources\BoardMembers\BoardMemberResource;
use App\Filament\Resources\BoardPeriods\BoardPeriodResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Divisions\DivisionResource;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Tags\TagResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/** Role & hak akses panel admin: semua pemeriksaan di server (policy/canAccess/validasi). */
class AksesRbacTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, Permission>
     */
    private function menus(): array
    {
        return [
            PostResource::getUrl('index') => Permission::Posts,
            PostResource::getUrl('create') => Permission::Posts,
            CategoryResource::getUrl('index') => Permission::Taxonomy,
            TagResource::getUrl('index') => Permission::Taxonomy,
            AlbumResource::getUrl('index') => Permission::Albums,
            AchievementResource::getUrl('index') => Permission::Achievements,
            BoardPeriodResource::getUrl('index') => Permission::Structure,
            DivisionResource::getUrl('index') => Permission::Structure,
            BoardMemberResource::getUrl('index') => Permission::Structure,
            ManageContact::getUrl() => Permission::Contact,
            UserResource::getUrl('index') => Permission::Users,
            RoleResource::getUrl('index') => Permission::Roles,
        ];
    }

    /** Tahu URL-nya saja tidak cukup: tiap menu hanya terbuka untuk hak aksesnya sendiri. */
    public function test_setiap_menu_hanya_terbuka_dengan_hak_aksesnya(): void
    {
        foreach (Permission::cases() as $permission) {
            $this->actingAsUserWith($permission);

            foreach ($this->menus() as $url => $required) {
                $this->get($url)->assertStatus($required === $permission ? 200 : 403);
            }
            $this->get(Filament::getPanel('admin')->getUrl())->assertOk(); // dasbor: semua pengguna panel
        }
    }

    public function test_hak_akses_tambahan_pengguna_berlaku_di_luar_role(): void
    {
        $user = $this->actingAsUserWith(Permission::Posts);
        $this->get(ManageContact::getUrl())->assertForbidden();

        $user->update(['permissions' => [Permission::Contact->value]]);
        $this->get(ManageContact::getUrl())->assertOk();
    }

    /** Komponen Livewire juga menolak (bukan hanya tombol yang disembunyikan). */
    public function test_komponen_livewire_menolak_tanpa_hak_akses(): void
    {
        $this->actingAsUserWith(Permission::Albums);
        $post = Post::factory()->create();

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])->assertForbidden();
        Livewire::test(ListUsers::class)->assertForbidden();
        Livewire::test(ManageContact::class)->assertForbidden();
        $this->assertModelExists($post);
    }

    public function test_super_admin_membuat_pengguna_dengan_role_dan_hak_akses_tambahan(): void
    {
        $this->actingAsAdmin();
        $editor = Role::query()->where('name', 'Editor')->sole();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Anggota Humas',
                'email' => 'Humas@CSIRT.test',
                'password' => 'password-awal-123',
                'password_confirmation' => 'password-awal-123',
                'role_id' => $editor->id,
                'permissions' => [Permission::Contact->value],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'humas@csirt.test')->sole();
        $this->assertNotNull($user->email_verified_at, 'dibuat operator: langsung terverifikasi');
        $this->assertTrue(Hash::check('password-awal-123', $user->password));
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
        $this->assertTrue($user->hasPermission(Permission::Posts));
        $this->assertTrue($user->hasPermission(Permission::Contact));
        $this->assertFalse($user->hasPermission(Permission::Users));
    }

    /** Pemegang "kelola pengguna" bukan Super Admin tidak bisa memberi lebih dari yang ia punya. */
    public function test_pengelola_pengguna_tidak_bisa_menaikkan_hak_akses(): void
    {
        $manager = $this->actingAsUserWith(Permission::Users, Permission::Posts);
        $postsOnly = Role::factory()->with(Permission::Posts)->create();
        $form = fn (array $extra): array => [
            'name' => 'Akun Baru',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password-awal-123',
            'password_confirmation' => 'password-awal-123',
            ...$extra,
        ];

        foreach ([Role::super(), Role::query()->where('name', 'Admin')->sole()] as $role) {
            Livewire::test(CreateUser::class)
                ->fillForm($form(['role_id' => $role->id]))
                ->call('create')
                ->assertHasFormErrors(['role_id']);
        }
        Livewire::test(CreateUser::class)
            ->fillForm($form(['role_id' => $postsOnly->id, 'permissions' => [Permission::Roles->value]]))
            ->call('create')
            ->assertHasFormErrors(['permissions']);
        $this->assertSame(1, User::query()->count(), 'tidak ada akun yang terbuat');

        Livewire::test(CreateUser::class)
            ->fillForm($form(['role_id' => $postsOnly->id]))
            ->call('create')
            ->assertHasNoFormErrors();

        // Akun Super Admin dan akun dengan hak akses lebih tinggi tidak bisa diurus.
        $super = User::factory()->create(['role_id' => Role::super()->id]);
        Livewire::test(EditUser::class, ['record' => $super->getRouteKey()])->assertForbidden();
        Livewire::test(ListUsers::class)
            ->assertTableActionHidden(DeleteAction::class, $super)
            ->assertTableActionHidden(DeleteAction::class, $manager); // akun sendiri
    }

    /**
     * Akun sendiri: role/hak akses tidak ikut tersimpan walau request direkayasa (field-nya juga
     * dinonaktifkan), dan akun sendiri tidak bisa dihapus.
     */
    public function test_tidak_bisa_mengubah_role_atau_menghapus_akun_sendiri(): void
    {
        $this->actingAsAdmin();
        $self = auth()->user();
        User::factory()->create(['role_id' => Role::super()->id]); // bukan satu-satunya Super Admin

        Livewire::test(EditUser::class, ['record' => $self->getRouteKey()])
            ->set('data.role_id', Role::query()->where('name', 'Editor')->value('id'))
            ->set('data.permissions', [Permission::Posts->value])
            ->set('data.name', 'Nama Baru')
            ->call('save')
            ->assertHasNoFormErrors();

        $self->refresh();
        $this->assertSame('Nama Baru', $self->name);
        $this->assertTrue($self->isSuperAdmin(), 'role akun sendiri tidak berubah');
        $this->assertNull($self->permissions);
        Livewire::test(ListUsers::class)->assertTableActionHidden(DeleteAction::class, $self);
        $this->assertFalse($self->can('delete', $self));
    }

    public function test_super_admin_terakhir_tidak_bisa_diturunkan(): void
    {
        $this->actingAsAdmin();
        $self = auth()->user();

        Livewire::test(EditUser::class, ['record' => $self->getRouteKey()])
            ->set('data.role_id', Role::query()->where('name', 'Editor')->value('id'))
            ->call('save')
            ->assertHasFormErrors(['role_id']);

        $this->assertTrue($self->fresh()->isSuperAdmin());
    }

    public function test_email_tidak_boleh_kembar_walau_beda_huruf_besar(): void
    {
        $this->actingAsAdmin();
        User::factory()->create(['email' => 'humas@csirt.test']);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Kembar', 'email' => 'HUMAS@csirt.test', 'password' => 'password-awal-123', 'password_confirmation' => 'password-awal-123'])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    public function test_super_admin_menurunkan_super_admin_lain(): void
    {
        $this->actingAsAdmin();
        $other = User::factory()->create(['role_id' => Role::super()->id]);
        $admin = Role::query()->where('name', 'Admin')->sole();

        Livewire::test(EditUser::class, ['record' => $other->getRouteKey()])
            ->fillForm(['role_id' => $admin->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($other->fresh()->isSuperAdmin());
        $this->assertSame(1, User::superAdminCount());
    }

    public function test_role_super_admin_terkunci_dan_role_terpakai_tidak_bisa_dihapus(): void
    {
        $this->actingAsAdmin();
        $editor = Role::query()->where('name', 'Editor')->sole();
        User::factory()->create(['role_id' => $editor->id]);
        $unused = Role::factory()->create();

        $this->get(RoleResource::getUrl('edit', ['record' => Role::super()]))->assertForbidden();
        Livewire::test(ListRoles::class)
            ->assertTableActionHidden(DeleteAction::class, Role::super())
            ->assertTableActionHidden(DeleteAction::class, $editor)
            ->callTableAction(DeleteAction::class, $unused);

        $this->assertModelMissing($unused);
        $this->assertModelExists($editor);
    }

    public function test_super_admin_mengubah_hak_akses_role_lewat_centang(): void
    {
        $this->actingAsAdmin();
        $editor = Role::query()->where('name', 'Editor')->sole();

        Livewire::test(EditRole::class, ['record' => $editor->getRouteKey()])
            ->fillForm(['permissions' => [Permission::Posts->value, Permission::Structure->value]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([Permission::Posts->value, Permission::Structure->value], $editor->fresh()->permissions);
    }

    public function test_pengelola_role_hanya_sebatas_hak_aksesnya_sendiri(): void
    {
        $this->actingAsUserWith(Permission::Roles, Permission::Posts);

        Livewire::test(CreateRole::class)
            ->fillForm(['name' => 'Galeri Saja', 'permissions' => [Permission::Albums->value]])
            ->call('create')
            ->assertHasFormErrors(['permissions']);
        Livewire::test(CreateRole::class)
            ->fillForm(['name' => 'Penulis', 'permissions' => [Permission::Posts->value]])
            ->call('create')
            ->assertHasNoFormErrors();

        // Role dengan hak akses di luar miliknya (Admin) tidak bisa diubah.
        $this->get(RoleResource::getUrl('edit', ['record' => Role::query()->where('name', 'Admin')->sole()]))->assertForbidden();
    }
}
