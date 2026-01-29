@extends('admin.layouts.app')

@section('title', 'مهامي | SERV.X')
@section('page_title', 'مهامي')

@section('content')
    <div class="space-y-6">

        {{-- Filters --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-soft p-5">
            <form class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                <div class="w-full sm:w-64">
                    <label class="text-xs text-slate-500">الحالة</label>
                    <select name="status"
                        class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2">
                        <option value="">الكل</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st }}" @selected($status === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <button
                    class="mt-5 sm:mt-6 px-4 py-2 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-semibold">
                    تطبيق
                </button>

                <a href="{{ route('tech.tasks.index') }}"
                    class="mt-5 sm:mt-6 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 font-semibold">
                    إعادة ضبط
                </a>
            </form>
        </div>

        {{-- List --}}
        <div
            class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-soft p-5">
            <h2 class="text-lg font-black">قائمة المهام</h2>

            <div class="mt-4 space-y-3">
                @forelse ($tasks as $o)
                    <div
                        class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <p class="font-bold">طلب #{{ $o->id }} — <span
                                    class="text-slate-500">{{ $o->status }}</span></p>
                            <p class="text-sm text-slate-500">
                                الشركة: {{ $o->company?->company_name ?? '-' }}
                                @if ($o->company?->phone)
                                    — {{ $o->company->phone }}
                                @endif
                            </p>
                        </div>

                        <a href="{{ route('tech.tasks.show', $o->id) }}"
                            class="px-3 py-2 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 text-sm font-semibold">
                            عرض
                        </a>
                    </div>
                @empty
                    <div class="p-6 text-center text-slate-500">لا توجد مهام.</div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $tasks->links() }}
            </div>
        </div>

    </div>
@endsection
