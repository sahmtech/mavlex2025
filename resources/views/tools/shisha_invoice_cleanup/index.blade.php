@extends('layouts.app')
@section('title', 'تنظيف فواتير الشيشة')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">تنظيف أصناف الشيشة من الفواتير</h1>
    <p class="text-muted" style="margin-top:8px;">
        صفحة داخلية بدون رابط في القائمة — ارفع ملف الإكسل ثم عاين قبل أي حذف.
    </p>
</section>

<section class="content">
    @if (session('status'))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert {{ session('status.success') ? 'alert-success' : 'alert-danger' }} alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    {{ session('status.msg') }}
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            @component('components.widget', ['title' => 'رفع ملف الإكسل'])
                {!! Form::open([
                    'url' => action([\App\Http\Controllers\ShishaInvoiceCleanupController::class, 'preview']),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data',
                ]) !!}
                    <div class="form-group">
                        {!! Form::label('file', 'ملف الإكسل (الصنف | رقم الفاتورة | التاريخ | الكمية):') !!}
                        {!! Form::file('file', ['class' => 'form-control', 'required' => true, 'accept' => '.xlsx,.xls,.csv']) !!}
                    </div>
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                        رفع ومعاينة
                    </button>
                {!! Form::close() !!}
            @endcomponent
        </div>
        <div class="col-md-4">
            @component('components.widget', ['title' => 'تعليمات مهمة'])
                <ol style="padding-right:18px; line-height:1.8;">
                    <li>الأعمدة: <b>الصنف</b>، <b>رقم الفاتورة</b>، <b>التاريخ</b>، <b>الكمية</b>.</li>
                    <li>بعد الرفع تظهر الفواتير والأسطر المستهدفة باللون الأحمر.</li>
                    <li>إذا كانت الفاتورة تحتوي فقط على هذه الأصناف → تُحذف الفاتورة كاملة بعد التأكيد.</li>
                    <li>إذا بقيت أصناف أخرى → تُحذف الأسطر الحمراء فقط ويُعاد حساب الإجمالي.</li>
                    <li>لا يوجد زر قائمة لهذه الصفحة — الدخول بالرابط فقط.</li>
                </ol>
            @endcomponent
        </div>
    </div>
</section>
@endsection
