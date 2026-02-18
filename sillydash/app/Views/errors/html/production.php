<!doctype html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">

    <title><?= lang('Errors.whoops') ?></title>

    <style>
        <?= preg_replace('#[\r\n\t ]+#', ' ', file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'debug.css')) ?>
    </style>
</head>

<body>

    <div class="container text-center">

        <h1 class="headline"><?= lang('Errors.whoops') ?></h1>

        <p class="lead"><?= lang('Errors.weHitASnag') ?></p>

        <?php if (isset($exception)): ?>
            <div style="text-align: left; margin-top: 2rem; background: #f8f9fa; padding: 1rem; border-radius: 4px;">
                <h3>Error Details:</h3>
                <pre><?= esc($exception->getMessage()) ?></pre>
                <p>File: <?= esc($exception->getFile()) ?> (Line: <?= esc($exception->getLine()) ?>)</p>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>