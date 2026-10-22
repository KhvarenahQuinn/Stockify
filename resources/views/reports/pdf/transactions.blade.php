<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Goods Receipt / Issue Report') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .meta { font-size: 9px; color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 3px 5px; text-align: left; }
        th { background: #f3f4f6; font-weight: bold; }
        tfoot td { font-weight: bold; background: #f9fafb; }
    </style>
</head>
<body>
<h1>{{ __('Goods Receipt / Issue Report') }}</h1>
<div class="meta">
    {{ $appName ?? config('app.name') }} &middot;
    {{ __('generated') }} {{ $generatedAt->translatedFormat('d M Y H:i') }} &middot;
    {{ __(':count rows', ['count' => $export->collection()->count()]) }}
</div>

<table>
    <thead>
    <tr>
        @foreach($export->headings() as $heading)
            <th>{{ $heading }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @forelse($export->collection() as $row)
        <tr>
            @foreach($export->map($row) as $cell)
                <td>{{ $cell }}</td>
            @endforeach
        </tr>
    @empty
        <tr>
            <td colspan="{{ count($export->headings()) }}">{{ __('No transactions match the selected filters.') }}</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>