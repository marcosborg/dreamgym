<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;

class NewCustomer extends Mailable
{
    public function __construct(public User $customer) {}

    public function build()
    {
        return $this->subject('Dream Gym — novo registo')->view('emails.new-customer');
    }
}
