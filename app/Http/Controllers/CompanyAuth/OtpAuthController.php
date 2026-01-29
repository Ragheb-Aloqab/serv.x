<?php

namespace App\Http\Controllers\CompanyAuth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class OtpAuthController extends Controller
{
    public function showPhoneForm()
    {
        return view('company.auth.login-phone');
    }

    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = trim($data['phone']);

        // ✅ OTP تجريبي
        $otp = (string) random_int(100000, 999999);

        // نخزن OTP في السيشن لمدة مؤقتة (10 دقائق مثلاً)
        Session::put('otp.phone', $phone);
        Session::put('otp.code', $otp);
        Session::put('otp.expires_at', now()->addMinutes(10)->timestamp);

        // ✅ سجل بالـ log للتجربة
        Log::info("[OTP-DEV] Company Login OTP", [
            'phone' => $phone,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10)->toDateTimeString(),
        ]);

        return redirect()
            ->route('company.verify')
            ->with('success', 'تم إرسال رمز التحقق (تجريبي). تحقق من ملف اللوق ✅');
    }

    public function showRegisterForm()
    {
        return view('company.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'min:2', 'max:190'],
            'phone' => ['required', 'string', 'max:20', 'unique:companies,phone'],
            'email' => ['nullable', 'email', 'max:190', 'unique:companies,email'],
        ]);

        $phone = trim($data['phone']);


        // 1) إنشاء الشركة
        $company = Company::create([
            'company_name'  => $data['name'],
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'password'     => Hash::make(Str::random(32))
        ]);

        // 2) إرسال OTP مباشرة بعد التسجيل
        $otp = (string) random_int(100000, 999999);

        Session::put('otp.phone', $phone);
        Session::put('otp.code', $otp);
        Session::put('otp.expires_at', now()->addMinutes(10)->timestamp);

        Log::info("[OTP-DEV] Company Register OTP", [
            'company_id' => $company->id,
            'phone' => $phone,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10)->toDateTimeString(),
        ]);

        // 3) تحويل لصفحة التحقق
        return redirect()
            ->route('company.verify')
            ->with('success', 'تم إنشاء الحساب. تم إرسال رمز التحقق (تجريبي) ✅');
    }

    public function showVerifyForm()
    {
        // لو ما في رقم محفوظ، رجعه لصفحة الجوال
        if (!Session::has('otp.phone')) {
            return redirect()->route('company.login');
        }

        return view('company.auth.verify-otp', [
            'phone' => Session::get('otp.phone'),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $savedPhone = Session::get('otp.phone');
        $savedOtp   = Session::get('otp.code');
        $expiresAt  = (int) Session::get('otp.expires_at', 0);

        if (!$savedPhone || !$savedOtp || !$expiresAt) {
            return redirect()->route('company.login')
                ->withErrors(['otp' => 'لا يوجد رمز صالح. أعد المحاولة.']);
        }

        if (now()->timestamp > $expiresAt) {
            Session::forget(['otp.phone', 'otp.code', 'otp.expires_at']);
            return redirect()->route('company.login')
                ->withErrors(['otp' => 'انتهت صلاحية الرمز. أعد الإرسال.']);
        }

        if ($data['otp'] !== $savedOtp) {
            return back()->withErrors(['otp' => 'رمز غير صحيح. حاول مرة أخرى.']);
        }

        // ✅ اجلب الشركة من قاعدة البيانات حسب رقم الجوال
        $company = Company::query()->where('phone', $savedPhone)->first();

        if (!$company) {
            Session::forget(['otp.phone', 'otp.code', 'otp.expires_at']);
            return redirect()->route('company.login')
                ->withErrors(['otp' => 'لا توجد شركة بهذا الرقم.']);
        }

        // ✅ سجل دخول فعلي بالـ guard:company
        Auth::guard('company')->login($company, remember: true);

        // ✅ نظّف بيانات OTP
        Session::forget(['otp.phone', 'otp.code', 'otp.expires_at']);

        // ✅ وجّه إلى داشبورد الشركة
        return redirect()
            ->route('company.dashboard')
            ->with('success', 'تم تسجيل الدخول بنجاح ✅');
    }

    public function logout(Request $request)
    {
        // ✅ خروج فعلي من guard:company
        Auth::guard('company')->logout();

        // تنظيف أي OTP محفوظ
        Session::forget(['otp.phone', 'otp.code', 'otp.expires_at']);

        // تنظيف الجلسة
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('company.login');
    }
}
