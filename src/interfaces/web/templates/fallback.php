<?php
/**
 * @var string $appClassname
 * @var string $appKernelBuild
 * @var string[] $modulesLoaded
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Charcoal Monolith App - L0 (Foundation)</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;200;300;400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            font-weight: 200;
            font-size: 1.1rem;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 300 !important;
        }

        .app-title {
            font-size: 1.2rem;
            font-weight: 300;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5 text-center">
    <div class="p-5 mb-4 bg-primary text-bg-primary rounded-3">
        <h1 class="display-4">🎉 Success! It's working.</h1>
        <hr class="my-4">
        <p class="mb-1">The
            <span class="badge text-bg-info app-title"><?= $appClassname ?></span>
            is running!
        </p>
        <p class="mb-0">You’re seeing this page because the request didn’t match any defined endpoint, so the fallback
            handler was
            used.</p>
    </div>

    <p class="text-center mb-2">Loaded modules:</p>
    <div class="d-flex mb-4 justify-content-center">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <a href="//github.com/charcoal-dev/app-kernel">charcoal-dev/app-kernel</a>:
                    <mark><?= $appKernelBuild ?></mark>
                </h5>
            </div>
            <ul class="list-group text-start list-group-flush">
                <?php foreach ($modulesLoaded as $module): ?>
                    <li class="list-group-item text-muted"><?= $module ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
</body>
</html>