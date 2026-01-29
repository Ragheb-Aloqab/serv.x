<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>فاتورة</title>

    {{-- Tailwind --}}
    @vite(['resources/css/app.css'])

    <style>
        @media print {
            body {
                background: white !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-white text-gray-900">

    @yield('content')

    {{-- طباعة تلقائية (اختياري) --}}
    <script>
        window.onload = function () {
            window.print();
        }
    </script>
</body>
</html>
