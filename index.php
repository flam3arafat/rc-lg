<?php declare(strict_types=1);
/**
 * RajshahiColo Looking Glass
 *
 * Provides UI and input for the looking glass backend.
 *
 * @copyright 2025 RajshahiColo.
 * * @license Apache License, Version 2.0
 * * @version 1.3.6
 * * @since File available since release 0.1
 * * @link https://rajshahicolo.com/
 */

require __DIR__.'/bootstrap.php';

use Hybula\LookingGlass;


$errorMessage = null;
if (!empty($_POST)) {
    if (!isset($_POST['csrfToken']) || !isset($_SESSION[LookingGlass::SESSION_CSRF]) || ($_POST['csrfToken'] !== $_SESSION[LookingGlass::SESSION_CSRF])) {
        exitErrorMessage('Missing or incorrect CSRF token.');
    }

    if (!isset($_POST['submitForm']) || !isset($_POST['backendMethod']) || !isset($_POST['targetHost'])) {
        exitErrorMessage('Unsupported POST received.');
    }

    if (!in_array($_POST['backendMethod'], LG_METHODS)) {
        exitErrorMessage('Unsupported backend method.');
    }

    $targetHost = htmlspecialchars($_POST['targetHost'], ENT_QUOTES, 'UTF-8');
    $_SESSION[LookingGlass::SESSION_TARGET_METHOD] = $_POST['backendMethod'];
    $_SESSION[LookingGlass::SESSION_TARGET_HOST]   = $targetHost;
    if (!isset($_POST['checkTerms']) && LG_TERMS) {
        exitErrorMessage('You must agree with the Terms of Service.');
    }

    if (in_array($_POST['backendMethod'], ['ping', 'mtr', 'traceroute'])) {
        if (!LookingGlass::isValidIpv4($_POST['targetHost']) &&
            !$targetHost = LookingGlass::isValidHost($_POST['targetHost'], LookingGlass::IPV4)
        ) {
            exitErrorMessage('No valid IPv4 provided.');
        }
    }

    if (in_array($_POST['backendMethod'], ['ping6', 'mtr6', 'traceroute6'])) {
        if (!LookingGlass::isValidIpv6($_POST['targetHost']) &&
            !$targetHost = LookingGlass::isValidHost($_POST['targetHost'],LookingGlass::IPV6)
        ) {
            exitErrorMessage('No valid IPv6 provided.');
        }
    }

    $_SESSION[LookingGlass::SESSION_TARGET_HOST]  = $targetHost;
    $_SESSION[LookingGlass::SESSION_TOS_CHECKED]  = true;
    $_SESSION[LookingGlass::SESSION_CALL_BACKEND] = true;
    exitNormal();
}

$templateData['session_target']       = $_SESSION[LookingGlass::SESSION_TARGET_HOST] ?? '';
$templateData['session_method']       = $_SESSION[LookingGlass::SESSION_TARGET_METHOD] ?? '';
$templateData['session_call_backend'] = $_SESSION[LookingGlass::SESSION_CALL_BACKEND] ?? false;
$templateData['session_tos_checked']  = isset($_SESSION[LookingGlass::SESSION_TOS_CHECKED]) ? ' checked' : '';

if (isset($_SESSION[LookingGlass::SESSION_ERROR_MESSAGE])) {
    $templateData['error_message'] = $_SESSION[LookingGlass::SESSION_ERROR_MESSAGE];
    unset($_SESSION[LookingGlass::SESSION_ERROR_MESSAGE]);
}

if (LG_BLOCK_CUSTOM) {
    if (defined('LG_CUSTOM_PHP') && file_exists(LG_CUSTOM_PHP)) {
        include LG_CUSTOM_PHP;
    }

    if (defined('LG_CUSTOM_HTML') && file_exists(LG_CUSTOM_HTML)) {
        ob_start();
        include LG_CUSTOM_HTML;
        $templateData['custom_html'] = ob_get_clean();
    }

    if (defined('LG_CUSTOM_HEADER_PHP') && file_exists(LG_CUSTOM_HEADER_PHP)) {
        ob_start();
        include LG_CUSTOM_HEADER_PHP;
        $templateData['custom_header'] = ob_get_clean();
    }

    if (defined('LG_CUSTOM_FOOTER_PHP') && file_exists(LG_CUSTOM_FOOTER_PHP)) {
        ob_start();
        include LG_CUSTOM_FOOTER_PHP;
        $templateData['custom_footer'] = ob_get_clean();
    }
}

if (LG_CHECK_LATENCY) {
    $templateData['latency'] = LookingGlass::getLatency();
}

$templateData['csrfToken'] = $_SESSION[LookingGlass::SESSION_CSRF] = bin2hex(random_bytes(12));
?>
<!doctype html>
<html lang="en" data-bs-theme="<?php if (LG_THEME != 'auto') echo LG_THEME; ?>">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta content="" name="description">
    <meta content="RajshahiColo" name="author">
    <title><?php echo $templateData['title'] ?></title>
    <link rel="icon" type="image/png" href="https://i.ibb.co.com/FbXrkLBx/rc-fav.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
    :root {
        --brand-primary: #0d6efd;
        --brand-primary-dark: #0b5ed7;
        --page-bg: #f4f7fb;
        --surface: #ffffff;
        --surface-muted: #f8fafc;
        --line: #e4e9f0;
        --text-muted: #687386;
    }

    body {
        background: linear-gradient(180deg, #eef5ff 0, var(--page-bg) 18rem, var(--page-bg) 100%);
        color: #172033;
        font-family: "Segoe UI", system-ui, sans-serif;
        min-height: 100vh;
    }

    .main-container {
        max-width: 1120px;
        width: 100%;
        padding-right: 1.25rem !important;
        padding-left: 1.25rem !important;
    }

    header {
        min-height: 4.5rem;
        border-color: rgba(120, 137, 160, .22) !important;
    }

    header > div:first-child a {
        min-height: 2.75rem;
    }

    header select {
        border-color: var(--line);
        box-shadow: 0 6px 18px rgba(42, 61, 90, .06);
    }

    .card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: .75rem;
        box-shadow: 0 12px 30px rgba(42, 61, 90, .07) !important;
    }

    .card-body {
        padding: 1.5rem !important;
    }

    .card-title {
        color: #172033;
        font-weight: 700;
        letter-spacing: .01em;
    }

    label,
    .text-muted {
        color: var(--text-muted) !important;
        font-size: .82rem;
        font-weight: 600;
        letter-spacing: .02em;
    }

    .form-control,
    .form-select,
    .input-group-text {
        border-color: var(--line);
        min-height: 2.7rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: rgba(13, 110, 253, .55);
        box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .1);
    }

    .input-group-text {
        background: var(--surface-muted);
        color: #4b5870;
        font-weight: 600;
    }

    .btn {
        min-height: 2.7rem;
        font-weight: 600;
    }

    .btn-outline-secondary {
        border-color: #cbd4e1;
        color: #4b5870;
    }

    .btn-outline-secondary:hover,
    .btn-outline-secondary:focus {
        background: #eef4fb;
        border-color: #aebbd0;
        color: #26364f;
    }

    .btn-group {
        gap: .5rem;
    }

    .btn-group > .btn {
        border-radius: .45rem !important;
    }

    #outputCard {
        border: 0;
        border-radius: .6rem;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .08) !important;
    }

    #outputContent {
        max-height: 28rem;
        overflow: auto;
        font-size: .82rem;
        line-height: 1.55;
    }

    footer {
        border-color: rgba(120, 137, 160, .22) !important;
    }

    [data-bs-theme="dark"] {
        --page-bg: #111827;
        --surface: #1a2332;
        --surface-muted: #202c3d;
        --line: #344156;
        --text-muted: #aab6c8;
    }

    [data-bs-theme="dark"] body {
        background: linear-gradient(180deg, #17263a 0, #111827 20rem, #111827 100%);
        color: #eef3f9;
    }

    [data-bs-theme="dark"] .card-title {
        color: #f4f7fb;
    }

    [data-bs-theme="dark"] .input-group-text,
    [data-bs-theme="dark"] .btn-outline-secondary:hover,
    [data-bs-theme="dark"] .btn-outline-secondary:focus {
        background: var(--surface-muted);
        color: #e5edf8;
    }

    .btn-primary {
        background-color: var(--brand-primary);
        border-color: var(--brand-primary);
    }

    .btn-primary:hover,
    .btn-primary:focus,
    .btn-primary:active {
        background-color: var(--brand-primary-dark);
        border-color: var(--brand-primary-dark);
    }

    .text-primary,
    a {
        color: var(--brand-primary);
    }

    @media (min-width: 992px) {
        .main-container {
            width: 92%;
        }
    }

    @media (max-width: 575.98px) {
        header {
            align-items: flex-start !important;
            flex-direction: column;
            gap: 1rem;
        }

        header > div,
        header > div:first-child,
        header > div:last-child {
            width: 100% !important;
        }

        .card-body {
            padding: 1.15rem !important;
        }

        .btn-group {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    </style>
    <?php if ($templateData['custom_css']) { echo '<link href="'.$templateData['custom_css'].'" rel="stylesheet">'; } ?>
    <?php if ($templateData['custom_head']) { echo $templateData['custom_head']; } ?>
</head>
<body>

<?php echo isset($templateData['custom_header']) ? $templateData['custom_header'] : '' ?>

<div class="main-container col-lg-8 mx-auto p-3 py-md-5">

    <header class="d-flex align-items-center pb-3 mb-5 border-bottom">
            <div class="col-8">
                <a class="d-flex align-items-center text-primary text-decoration-none color-mode-choice color-mode-light-visible" href="<?php echo $templateData['logo_url'] ?>" target="_blank">
                    <?php echo $templateData['logo_data'] ?>
                </a>
                <a class="d-flex align-items-center text-primary text-decoration-none color-mode-choice color-mode-dark-visible" href="<?php echo $templateData['logo_url'] ?>" target="_blank">
                    <?php echo $templateData['logo_data_dark'] ?>
                </a>
            </div>
            <div class="col-4 float-end">
                <select class="form-select" onchange="updateLocation(this.value)" <?php if (count($templateData['locations']) == 0) echo 'disabled'; ?>>
                    <option value="<?php echo $templateData['current_location'] ?>" selected><?php echo $templateData['current_location'] ?></option>
                    <?php foreach ($templateData['locations'] as $location => $link): ?>
                        <?php if ($location !== $templateData['current_location']): ?>
                            <option value="<?php echo $location ?>"><?php echo $location ?></option>
                        <?php endif ?>
                    <?php endforeach ?>
                </select>
            </div>
    </header>

    <main>

        <?php if (LG_BLOCK_NETWORK): ?>
        <div class="row mb-5">
            <div class="card shadow-lg">
                <div class="card-body p-3">
                    <h1 class="fs-4 card-title mb-4">Network</h1>

                    <div class="row mb-3">
                        <div class="col-md-7">
                            <label class="mb-2 text-muted">Location</label>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" value="<?php echo $templateData['current_location'] ?>" onfocus="this.select()" readonly="" data-current-location>
                                <a class="btn btn-outline-secondary" href="https://www.openstreetmap.org/search?query=<?php echo urlencode($templateData['maps_query']); ?>" target="_blank">Map</a>
                                <?php if (!empty($templateData['locations'])): ?>
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Locations</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php foreach ($templateData['locations'] as $location => $link): ?>
                                    <li><a class="dropdown-item" href="#" data-location="<?php echo $location ?>" onclick="updateLocation(this.dataset.location); return false;"><?php echo $location ?></a></li>
                                    <?php endforeach ?>
                                </ul>
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="mb-2 text-muted">Facility</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?php echo $templateData['facility'] ?>" onfocus="this.select()" readonly="">
                                <a href="<?php echo $templateData['facility_url'] ?>" class="btn btn-outline-secondary" target="_blank">PeeringDB</a>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="mb-2 text-muted">Looking Glass IPv4</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?php echo $templateData['ipv4'] ?>" onfocus="this.select()" readonly="">
                                <button class="btn btn-outline-secondary" onclick="copyToClipboard('<?php echo $templateData['ipv4'] ?>', this)">Copy</button>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="mb-2 text-muted">Looking Glass IPv6</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?php echo $templateData['ipv6'] ?>" onfocus="this.select()" readonly="">
                                <button class="btn btn-outline-secondary" onclick="copyToClipboard('<?php echo $templateData['ipv6'] ?>', this)">Copy</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="mb-2 text-muted">Your IP</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?php echo $templateData['user_ip'] ?>" onfocus="this.select()" readonly="">
                                <?php if (LG_CHECK_LATENCY): ?><label class="input-group-text" title="Latency between this looking glass and your connection." style="cursor: help;"><small><?php echo $templateData['latency'] ?> MS</small></label><?php endif ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php endif ?>

        <?php if (LG_BLOCK_LOOKINGGLASS): ?>
        <div class="row pb-5">
            <div class="card shadow-lg">
                <div class="card-body p-3">
                    <h1 class="fs-4 card-title mb-4">Looking Glass</h1>
                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="csrfToken" value="<?php echo $templateData['csrfToken'] ?>">

                        <div class="row">
                            <div class="col-md-7 mb-3">
                                <div class="input-group">
                                    <span class="input-group-text" id="basic-addon1">Target</span>
                                    <input type="text" class="form-control" placeholder="IP address or host..." name="targetHost" value="<?php echo $templateData['session_target'] ?>" required="">
                                </div>
                            </div>
                            <div class="col-md-5 mb-3">
                                <div class="input-group">
                                    <label class="input-group-text">Method</label>
                                    <select class="form-select" name="backendMethod" id="backendMethod">
                                        <?php foreach ($templateData['methods'] as $method): ?>
                                            <option value="<?php echo $method ?>"<?php if($templateData['session_method'] === $method): ?> selected<?php endif ?>><?php echo $method ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center">
                            <?php if ($templateData['tos']): ?>
                            <div class="form-check">
                                <input type="checkbox" id="checkTerms" name="checkTerms" class="form-check-input"<?php echo $templateData['session_tos_checked'] ?>>
                                <label for="checkTerms" class="form-check-label">I agree with the <a href="<?php echo $templateData['tos'] ?>" target="_blank">Terms of Use</a></label>
                            </div>
                            <?php endif ?>
                            <button type="submit" class="btn btn-primary ms-auto" id="executeButton" name="submitForm">
                                Execute
                            </button>
                        </div>

                        <?php if ($templateData['error_message']): ?>
                        <div class="alert alert-danger mt-3" role="alert"><?php echo $templateData['error_message'] ?></div>
                        <?php endif ?>

                        <div class="card card-body bg-dark text-light mt-4 d-none" id="outputCard">
                            <pre id="outputContent" class="mb-0"></pre>
                        </div>
                    </form>

                </div>
            </div>
        </div>
        <?php endif ?>

        <?php if (LG_BLOCK_SPEEDTEST): ?>
        <div class="row pb-5">
            <div class="card shadow-lg">
                <div class="card-body p-3">
                    <h1 class="fs-4 card-title mb-4">Speedtest</h1>

                    <?php if ($templateData['speedtest_iperf']): ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="mb-2 text-muted"><?php echo $templateData['speedtest_incoming_label'] ?></label>
                            <p><code><?php echo $templateData['speedtest_incoming_cmd']; ?></code></p>
                            <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('<?php echo $templateData['speedtest_incoming_cmd'] ?>', this)">Copy</button>
                        </div>
                        <div class="col-md-6">
                            <label class="mb-2 text-muted"><?php echo $templateData['speedtest_outgoing_label'] ?></label>
                            <p><code><?php echo $templateData['speedtest_outgoing_cmd'] ?></code></p>
                            <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('<?php echo $templateData['speedtest_outgoing_cmd'] ?>', this)">Copy</button>
                        </div>
                    </div>
                    <?php endif ?>
    
                    <?php if (count($templateData['speedtest_files'])): ?>
                    <div class="row">
                        <label class="mb-2 text-muted">Test Files</label>
                        <div class="btn-group input-group mb-3">
                            <?php foreach ($templateData['speedtest_files'] as $file => $link): ?>
                                <a href="<?php echo $link ?>" class="btn btn-outline-secondary"><?php echo $file ?></a>
                            <?php endforeach ?>
                        </div>
                    </div>
                    <?php endif ?>

                </div>
            </div>
        </div>
        <?php endif ?>

        <?php echo $templateData['custom_html'] ?>

    </main>
    <footer class="pt-3 mt-5 my-5 text-muted border-top">
        Powered by <a href="https://rajshahicolo.com/" target="_blank">RajshahiColo</a>
    </footer>
</div>

<script type="text/javascript">
    function updateLocation(location) {
        document.querySelectorAll('[data-current-location]').forEach((element) => {
            element.value = location
        })
    }

    function setThemeClass() {
        const colorMode = document.querySelector("html").getAttribute("data-bs-theme");
        const allDivs = document.querySelectorAll('.color-mode-choice')
        allDivs.forEach((div) => {
            div.classList.add('d-none')
            if (div.matches('.color-mode-' + colorMode + '-visible')){
                div.classList.remove('d-none')
            }
        })
    };
    setThemeClass();
</script>

<?php if (LG_THEME == 'auto'): ?>
<script type="text/javascript">
    function updateThemeHelper() {
        const colorMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        document.querySelector("html").setAttribute("data-bs-theme", colorMode);
        setThemeClass();
    }
    updateThemeHelper();
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateThemeHelper);
</script>
<?php endif ?>

<?php echo isset($templateData['custom_footer']) ? $templateData['custom_footer'] : '' ?>

<?php if ($templateData['session_call_backend']): ?>
<script type="text/javascript">
    (function () {
        const outputContent = document.getElementById('outputContent')
        const executeButton = document.getElementById('executeButton')
        const outputCard = document.getElementById('outputCard')

        executeButton.innerText = 'Executing...'
        executeButton.disabled = true

        outputCard.classList.toggle('d-none')

        fetch('backend.php')
            .then(async (response) => {
                // response.body is a ReadableStream
                const reader = response.body.getReader()
                const decoder = new TextDecoder()

                for await (const chunk of readChunks(reader)) {
                    const text = decoder.decode(chunk)
                    <?php if(in_array($_SESSION[LookingGlass::SESSION_TARGET_METHOD], [LookingGlass::METHOD_MTR, LookingGlass::METHOD_MTR6])): ?>
                    let splittedText = text.split('@@@')
                    if (!splittedText[1]) {
                        continue
                    }
                    outputContent.innerHTML = splittedText[1].trim()
                    <?php else: ?>
                    outputContent.innerHTML = outputContent.innerHTML + text.trim().replace(/<br \/> +/g, '<br />')
                    <?php endif ?>
                }
            })
            .finally(() => {
                executeButton.innerText = 'Execute'
                executeButton.disabled = false
                console.log('Backend ready!')
            })
    })()

    // readChunks() reads from the provided reader and yields the results into an async iterable
    function readChunks(reader) {
        return {
            async* [Symbol.asyncIterator]() {
                let readResult = await reader.read()
                while (!readResult.done) {
                    yield readResult.value
                    readResult = await reader.read()
                }
            },
        }
    }
</script>
<?php endif ?>

<script type="text/javascript">
    async function copyToClipboard(text, button) {
        if (!navigator || !navigator.clipboard || !navigator.clipboard.writeText) {
            return Promise.reject('The Clipboard API is not available.')
        }

        button.innerHTML = 'Copied!'
        await navigator.clipboard.writeText(text)
        await new Promise(r => setTimeout(r, 2000))
        button.innerHTML = 'Copy'
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

</body>
</html>
