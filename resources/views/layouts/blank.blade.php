<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap"
        rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Sarabun', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            background-color: transparent;
            margin: 0;
            padding: 0;
        }

        .content-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #fecce3;
            /* Light pink border */
            position: relative;
            padding-left: 20px;
        }

        .content-header:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 15px;
            width: 5px;
            background: linear-gradient(to bottom, #f06292, #ec407a);
            border-radius: 10px;
        }

        .content-header h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: #2d3436;
            margin: 0;
        }

        .main-content {
            padding: 20px;
        }
    </style>
    @yield('extra_css')
</head>

<body style="background: transparent;">
    <main class="main-content">
        @php
            // Callers that embed these pages inside an admin "ภาพรวม" iframe
            // (hideLayout=1) set header_title to an EMPTY string rather than
            // omitting the section entirely - hasSection() alone still
            // returns true for that, so the pink content-header divider
            // used to render with a blank <h1>, showing as a stray pink
            // line with empty space above/below it and nothing else. Only
            // render the header block when there is actual title text.
            $headerTitleContent = trim((string) View::yieldContent('header_title'));
        @endphp
        @if($headerTitleContent !== '')
            <div class="content-header">
                <h1>{!! $headerTitleContent !!}</h1>
            </div>
        @endif

        @yield('content')
    </main>
    @yield('extra_js')
</body>

</html>