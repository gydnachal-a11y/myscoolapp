<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Mail\BulkEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class BulkEmailController extends Controller
{
    public function compose()
    {
        return view('admin.emails.compose');
    }

    public function send(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $contacts = Contact::all();
        $count = 0;

        foreach ($contacts as $contact) {
            Mail::to($contact->email)->send(new BulkEmail($request->subject, $request->content));
            $count++;
        }

        return redirect()->route('admin.emails.compose')->with('success', "Email envoyé à {$count} abonnés.");
    }
}