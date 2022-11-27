<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="canonical" href="https://https://demo.themesberg.com/landwind/"/>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    <meta name="title" content="Landwind - Tailwind CSS Landing Page">
    <meta name="description"
          content="Get started with a free and open-source landing page built with Tailwind CSS and the Flowbite component library.">
    <meta name="robots" content="index, follow">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="language" content="English">
    <meta property="og:title" content="Landwind - Tailwind CSS Landing Page">
    <meta property="og:site_name" content="Themesberg">
    <meta property="og:url" content="https://https://demo.themesberg.com/landwind/">
    <meta property="og:description"
          content="Get started with a free and open-source landing page for Tailwind CSS built with the Flowbite component library featuring dark mode, hero sections, pricing cards, and more.">
    <meta property="og:type" content="">
    <meta property="og:image"
          content="https://themesberg.s3.us-east-2.amazonaws.com/public/github/landwind/og-image.png">
    <meta name="twitter:card" content="summary"/>
    <meta name="twitter:site" content="@themesberg"/>
    <meta name="twitter:creator" content="@themesberg"/>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">
    <link href="/output.css" rel="stylesheet">
    <script async defer src="https://buttons.github.io/buttons.js"></script>
</head>
<body>
@include("landwind.header")
<section class="bg-gray-50 dark:bg-gray-800">
    <h1 class="max-w-screen-xl px-4 py-1 mx-auto text-center lg:py-10 lg:px-6">
        {{ $header ?? "" }}
    </h1>
</section>
<section class="bg-gray-50 dark:bg-gray-800">
    <div class="max-w-screen-xl px-4 py-1 mx-auto text-center lg:py-10 lg:px-6">
        {{ $slot }}
    </div>
</section>
@include('_template.landwind.footer')

<script src="https://unpkg.com/flowbite@1.4.1/dist/flowbite.js"></script>
</body>
</html>
