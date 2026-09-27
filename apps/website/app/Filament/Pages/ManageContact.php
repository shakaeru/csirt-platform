<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Contact\ContactPerson;
use App\Support\Contact\RegistrationStatus;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

/**
 * Pengaturan halaman Kontak yang berubah tanpa deploy: status pendaftaran anggota (tautan Google
 * Form) dan contact person. Disimpan di tabel settings, bukan di repo (repo publik).
 *
 * @property-read Schema $form
 */
class ManageContact extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $navigationLabel = 'Kontak & Pendaftaran';

    protected static ?string $title = 'Kontak & Pendaftaran';

    protected static ?string $slug = 'kontak';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'registration' => Setting::get(RegistrationStatus::SETTING_KEY, ['enabled' => false]),
            'contact_people' => Setting::get(ContactPerson::SETTING_KEY, []),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pendaftaran anggota')
                    ->description('Formulir memakai Google Form milik akun CSIRT; jawaban pendaftar tersimpan di Google Spreadsheet, bukan di website.')
                    ->statePath('registration')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Pendaftaran dibuka')
                            ->helperText('Mati = halaman Kontak menampilkan "Pendaftaran belum dibuka".')
                            ->live(),
                        TextInput::make('form_url')
                            ->label('Tautan formulir')
                            ->placeholder('https://forms.gle/…')
                            ->helperText('Tautan "Kirim" dari Google Form. Jangan lupa aktifkan "Menerima respons" di Google Form-nya.')
                            ->url()
                            // Hanya https — URL lain (mis. javascript:) tidak boleh jadi tautan di situs.
                            ->rule('url:https')
                            ->maxLength(500)
                            ->required(fn (Get $get): bool => (bool) $get('enabled')),
                        DatePicker::make('closes_on')
                            ->label('Hari terakhir pendaftaran')
                            ->helperText('Opsional. Lewat dari tanggal ini (WIB), halaman Kontak otomatis menampilkan "Sudah ditutup" walaupun saklar masih menyala.'),
                    ]),
                Section::make('Contact person')
                    ->description('Nama, jabatan, dan nomor tampil publik di halaman Kontak (dengan tautan WhatsApp). Perbarui saat pengurus berganti.')
                    ->schema([
                        Repeater::make('contact_people')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('position')
                                    ->label('Jabatan')
                                    ->placeholder('mis. Sekretaris 1')
                                    ->required()
                                    ->maxLength(60),
                                TextInput::make('phone')
                                    ->label('Nomor WhatsApp')
                                    ->placeholder('08xx-xxxx-xxxx')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20)
                                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                        if (ContactPerson::normalizePhone((string) $value) === null) {
                                            $fail('Isi nomor seluler Indonesia, mis. 0812-3456-7890.');
                                        }
                                    }),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->maxItems(4)
                            ->reorderable()
                            ->addActionLabel('Tambah contact person')
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Simpan')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ])->key('form-actions'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        DB::transaction(function () use ($data): void {
            Setting::put(RegistrationStatus::SETTING_KEY, [
                'enabled' => (bool) ($data['registration']['enabled'] ?? false),
                'form_url' => $data['registration']['form_url'] ?? null,
                'closes_on' => $data['registration']['closes_on'] ?? null,
            ]);
            Setting::put(ContactPerson::SETTING_KEY, collect($data['contact_people'] ?? [])
                ->map(fn (array $person): array => [
                    'name' => trim($person['name']),
                    'position' => trim($person['position']),
                    'phone' => trim($person['phone']),
                ])
                ->values()
                ->all());
        });

        Notification::make()
            ->success()
            ->title('Tersimpan — halaman Kontak sudah diperbarui')
            ->send();
    }
}
