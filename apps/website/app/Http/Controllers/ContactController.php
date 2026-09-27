<?php

namespace App\Http\Controllers;

use App\Support\Contact\ContactPerson;
use App\Support\Contact\RegistrationStatus;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * Halaman publik Kontak — kanal resmi dari config/kontak.php, contact person dan status
     * pendaftaran dari panel admin.
     */
    public function __invoke(): View
    {
        return view('kontak', [
            'kontak' => config('kontak'),
            'contactPeople' => ContactPerson::all(),
            'registration' => RegistrationStatus::current(),
        ]);
    }
}
