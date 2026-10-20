<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        $seo = Seo::make(
            'Nanybot — Repeated tasks automated. For business owners.',
            'Nanybot builds software that takes over repeated business tasks. Cut cost by 70-80%. Laptop Care and Nany Social, with more on the way.',
            route('home'),
        );

        return view('landing', compact('seo'));
    }
}
