<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    <!-- Meta SEO -->
    <meta name="title" content="{{ $meta_title ?? config("meta_title","some text") }}">
    <meta name="description" content="{{ $meta_description ?? config("meta_description","some text") }}">
    <meta name="robots" content="index, follow">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="language" content="English">

    <!-- Social media share -->
    <meta property="og:title" content="{{ $title ?? config('app.name', 'Laravel') }}">
    <meta property="og:site_name" content="{{ config('app.name', 'Laravel') }}">
    <meta property="og:url" content="{{ env("APP_URL") }}">
    <meta property="og:description" content="{{ $meta_description ?? config("meta_description","some text") }}">
    <meta property="og:type" content="">
    <meta property="og:image" content="{{ $meta_image ?? config("meta_image","some text") }}">
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:site" content="@pforret" />
    <meta name="twitter:creator" content="@pforret" />

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@4.5.2/dist/litera/bootstrap.min.css" integrity="sha384-enpDwFISL6M3ZGZ50Tjo8m65q06uLVnyvkFO3rsoW0UC15ATBFz3QEhr3hmxpYsn" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.0/css/fontawesome.min.css" integrity="sha384-z4tVnCr80ZcL0iufVdGQSUzNvJsKjEtqYZjiQrrYKlpGow+btDHDfQWkFjoaz/Zr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css" integrity="sha384-xeJqLiuOvjUBq3iGOjvSQSIlwrpqjSHXpduPd6rQpuiM3f5/ijby8pCsnbu5S81n" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.min.js" integrity="sha384-IDwe1+LCz02ROU9k972gdyvl+AESN10+x7tBKgc9I5HFtuNz0wWnPclzo6p9vxnk" crossorigin="anonymous"></script>
    <link href="/output.css" rel="stylesheet">
</head>
<body>
@include('_template.bootstrap.navigation')
<main>
    <section class="py-5 text-center container">
        <div class="row py-lg-5">
            <div class="col-lg-6 col-md-8 mx-auto">
                <h1 class="text-4xl font-weight-bolder">{{ $title ?? config('app.name', 'Laravel') }}</h1>
                <p class="lead text-muted">{{ $short ?? "Short description" }}</p>
            </div>
        </div>
    </section>

    <div class="album py-5 bg-light">
        <div class="container">
