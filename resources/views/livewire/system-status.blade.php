<div class="flex min-h-screen flex-col">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
            <a href="{{ route('home') }}" class="text-lg font-bold text-green-700"><x-brand /></a>
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-700">داشبورد</a>
            @else
                <a href="{{ route('login') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-green-700 transition hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-700">ورود</a>
            @endauth
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-6xl flex-1 items-center px-6 py-16 sm:py-24">
        <div class="grid w-full items-center gap-12 md:grid-cols-2 md:gap-20">
            <div>
                <p class="text-sm font-semibold text-green-600">مدیریت یکپارچه گلخانه</p>
                <h1 class="mt-5 text-3xl font-bold leading-relaxed text-gray-900 sm:text-4xl sm:leading-relaxed">اطلاعات گلخانه،<br>در یک جای مطمئن</h1>
                <p class="mt-5 max-w-lg text-base leading-8 text-gray-600">اطلاعات گلخانه و فعالیت‌های خود را یکجا مدیریت کنید و ارتباط ساده‌تری با کارشناسان داشته باشید.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-green-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-700">رفتن به داشبورد</a>
                    @else
                        <a href="{{ route('register') }}" class="rounded-lg bg-green-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-700">ایجاد حساب کاربری</a>
                        <a href="{{ route('login') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-green-700 transition hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-700">ورود به حساب</a>
                    @endauth
                </div>
            </div>
            <div class="rounded-3xl border border-gray-200 bg-white p-7 shadow-sm sm:p-10">
                <p class="text-sm font-semibold text-green-600">یک شروع ساده</p>
                <h2 class="mt-3 text-xl font-bold text-gray-900">همراه گلخانه‌داران و کارشناسان</h2>
                <div class="mt-7 space-y-5 border-t border-gray-200 pt-6 text-sm leading-7 text-gray-700">
                    <p>ثبت اطلاعات و دسترسی آسان به حساب کاربری</p>
                    <p>سامان‌دهی اطلاعات گلخانه در یک فضای متمرکز</p>
                    <p>آماده برای توسعه امکانات تخصصی در آینده</p>
                </div>
            </div>
        </div>
    </main>
    <footer class="border-t border-gray-200 bg-white"><div class="mx-auto max-w-6xl px-6 py-5 text-xs text-gray-500"><x-brand /></div></footer>
</div>
