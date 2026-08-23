<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale) { abort_unless(in_array($locale, ['en','ar'], true), 404); session(['locale'=>$locale]); return back(); }
}
