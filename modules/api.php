<?php

function api_getmoduleinfo(): array
{
    return [
        'name' => 'API',
        'author' => 'Stephen Kise',
        'version' => '0.1.1',
        'category' => 'Administrative',
        'description' =>
            'Adds an API callback, mainly for the use of JavaScript updates.',
        'allowanonymous' => true,
        'override_forced_nav' => true,
     ];
}

function api_install(): bool
{
    module_addhook('superuser');
    return true;
}

function api_uninstall(): bool
{
    return true;
}

function api_dohook(string $hook, array $args): array
{
    addnav('Mechanics');
    addnav('Read the API', 'runmodule.php?module=api&op=docs');
    return $args;
}

function api_run(): bool
{
    global $output, $session, $apiRequestMethod, $payload, $apiFunctions;
    $apiRequestMethod = $_SERVER['REQUEST_METHOD'];
    $payload = file_get_contents('php://input');
    $args = modulehook(
        'api',
        [
            'api' => [
                'help' => [
                    'apiHelp',
                    'GET only the documentation ($apiFunctions) of the API module hook'
                ]
            ]
        ]
    );
    $apiFunctions = $args;
    $act = httpget('act')?:'';
    $mod = httpget('mod')?:'';
    switch (httpget('op')) {
        case 'docs':
            page_header('API Docs');
            output(
                "`@`bAPI Documentation`b`n
                `^Each module that hooks into the API is required to have documentation
                for the module and all of the actions that are possible.`n`n"
            );
            $documentation = apiPayload('api', 'help', $args);
            foreach ($documentation['args'] as $module => $action) {
                foreach ($action as $function => $data) {
                    output(
                        '`i`@/%s`Q:%s`2%s`Q:%s`i `^%s()`n`#&bull; %s`n`n',
                        'runmodule.php?module=api&mod=',
                        $module,
                        '&act=',
                        $function,
                        $data[0],
                        $data[1],
                        true
                    );
                }
            }
            addnav("Go back", "superuser.php");
            page_footer();
            break;
        default:
            $output = '';
            header('Content-Type: application/json');
            print_r(
                json_encode(
                    apiPayload($mod, $act, $args),
                    JSON_PRETTY_PRINT
                )
            );
            die();
            break;
    }
    return true;
}

function apiPayload(string $module, string $action, array $args): array
{
    $functionName = $args[$module][$action][0];
    if (!function_exists($functionName?:'')) {
        return [
            'status' => false,
            'errorMessage' => 'No such module and action pairing exists!',
        ];
    }
    return $functionName();
}

function apiHelp(): array
{
    global $apiFunctions;
    return [
        'status' => false,
        'errorMessage' =>
            'You need :mod and :act params, and your function needs to return an array!',
        'args' => $apiFunctions
    ];
}