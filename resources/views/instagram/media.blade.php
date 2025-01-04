<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Media</title>
</head>
<body>
    <h1>Instagram Media</h1>

    <ul>
        @foreach ($media['data'] as $item)
            <li>
                <img src="{{ $item['media_url'] }}" alt="{{ $item['caption'] }}" width="200" />
                <p>{{ $item['caption'] }}</p>
                <a href="{{ $item['permalink'] }}" target="_blank">View on Instagram</a>
            </li>
        @endforeach
    </ul>
</body>
</html>