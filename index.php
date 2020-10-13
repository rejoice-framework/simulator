<?php

    $hostAutoload = __DIR__.'/../../../autoload.php';
    $localAutoload = __DIR__.'/vendor/autoload.php';

    require_once file_exists($hostAutoload) ? $hostAutoload : $localAutoload;

    use function Prinx\Dotenv\env;
    use Prinx\Utils\DB;

    $env = env('APP_ENV', 'prod');
    $rawSimulatorData = '{}';

    $jsonFile = realpath(__DIR__.'/../../../../simulator.json') ?:
    realpath(__DIR__.'/simulator.json');

    if (file_exists($jsonFile)) {
        $rawSimulatorData = file_get_contents($jsonFile);
    }

    $data = json_decode($rawSimulatorData, true);
    $networks = $data['networks'] ?? [];

    function groupUssdBy(string $column, array $ussds)
    {
        $columns = ['id', 'app_name', 'network', 'code', 'url'];

        if (!in_array($column, $columns)) {
            return [];
        }

        $grouped = [];
        foreach ($ussds as $ussd) {
            if (!isset($grouped[$ussd[$column]])) {
                $grouped[$ussd[$column]] = [];
            }

            $group = [];
            foreach ($columns as $value) {
                if ($value !== $column) {
                    $group[$value] = $ussd[$value];
                }
            }

            $grouped[$ussd[$column]][] = $group;
        }

        return $grouped;
    }

    function retrieveSavedUssdEndpoints()
    {
        $params = [
            'driver'   => env('USSD_ENDPOINT_DRIVER', 'mysql'),
            'host'     => env('USSD_ENDPOINT_HOST', 'localhost'),
            'port'     => env('USSD_ENDPOINT_PORT', 3306),
            'dbname'   => env('USSD_ENDPOINT_DB', ''),
            'user'     => env('USSD_ENDPOINT_DB_USER', ''),
            'password' => env('USSD_ENDPOINT_DB_PASS', ''),
        ];

        try {
            $db = DB::load($params);
        } catch (\Throwable $th) {
            return [];
        }

        $ussdTable = env('USSD_ENDPOINT_DB_TABLE', '');
        $numUssdEnpointsToRetrieve = env('USSD_ENDPOINT_NUM_TO_RETRIEVE', 300);

        $stmt = $db->prepare("SELECT * FROM `$ussdTable` ORDER BY id DESC LIMIT :to_retrieve");
        $stmt->bindParam('to_retrieve', $numUssdEnpointsToRetrieve, \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    $ussds = retrieveSavedUssdEndpoints();
    $endpoints = groupUssdBy('endpoint', $ussds);

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>USSD SIMULATOR</title>
    <link rel="icon" href="favicon.png" />
    <link rel="stylesheet" href="src/public/css/bootstrap.min.css" />
    <link rel="stylesheet" href="src/public/css/style.css">
</head>

<body style="line-height:normal; font-size:initial; font-family:initial; color:initial">
    <header>
        <div class="toggle-controls" title="Controls">&Congruent;</div>
        <div class="m-2 ml-4" style="display:inline-block;"><a href="src/app/network.php" class="ml-4">Manage
                Networks</a> </div>
        <div class="m-2" style="display:inline-block;"><a href="src/app/phone.php">Manage Phones</a></div>
    </header>

    <main class="container">
        <div id="controls" class="controls-hidden">
            <form>
                <div class="px-3 pt-1 m-0" style="font-size:0.7rem">QUICK CONFIG</div>
                <hr>
                <div class="form-field form-group" title="The application's endpoint">
                    <label class="text-primary" for="endpoint">THE USSD APP TO TEST: </label> <br>
                    <select id="retrieved-endpoints" class="custom-select">
                        <?php if ($endpoints) {?>
                        <option selected disabled>Choose a saved endpoint</option>

                        <?php foreach ($endpoints as $url => $endpointData) {?>
                        <option data-code="<?php echo $endpointData[0]['code'] ?? '' ?>" value="<?php echo $url ?>"
                                title="<?php echo $url ?>">
                            <?php echo $endpointData[0]['name'] ?: $url ?></option>
                        <?php }?>
<?php } else {?>
                        <option selected disabled>No saved endpoint found</option>
                        <?php }?>
                    </select>
                </div>

                <div class="form-field form-group">
                    <label class="text-primary" for="retrieved-phone-number">THE PHONE NUMBER TO USE: </label> <br>
                    <select id="retrieved-phone-number" class="custom-select">
                        <option selected disabled>Choose a test phone</option>
                        <?php foreach ($networks as $networkName => $networkData) {
    $testPhones = $networkData['test_phones'] ?? []?>
                        <optgroup label="<?php echo $networkName ?>">
                            <?php foreach ($testPhones as $number => $phoneData) {?>
                            <option data-mnc="<?php echo $networkData['mnc'] ?? '' ?>" value="<?php echo $number ?>">
                                <?php echo $phoneData['name'] ?? $number ?></option>
                            <?php } ?>
                        </optgroup>
                        <?php
}?>
                    </select>
                </div>
                <div class="form-field form-group">
                    <label class="text-primary text-uppercase" for="retrieved-networks">NETWORK: </label> <br>
                    <select name="retrieved-networks" id="retrieved-networks" class="custom-select">
                        <?php if ($networks) {?>
                        <option disabled selected>Select a network</option>

                        <?php foreach ($networks as $networkName => $networkData) {?>
                        <option value="<?php echo $networkData['mnc'] ?>">
                            <?php echo $networkName ?>
                        </option>
                        <?php }?>
<?php } else {?>
                        <option disabled selected>No network configured.</option>
                        <?php }?>
                    </select>
                    <small class="text-muted unknown-network-error" style="font-size:10px;display:none;"><br>Network and
                        number mismatch</small>
                </div>

                <hr>
                <div class="px-3 pt-1 m-0" style="font-size:0.7rem">CUSTOM CONFIG</div>
                <hr>

                <!-- <div class="form-field mb-1 p-1 rounded alert-info">Custom Config</div> -->
                <div class="form-field form-group" title="The application's endpoint">
                    <label class="text-primary" for="endpoint">APPLICATION'S ENDPOINT: </label>
                    <br>
                    <input name="endpoint" id="endpoint" type="url" autofocus autocomplete="on"
                           value="<?php echo env('USSD_URL', '') ?>" placeholder="https://..." />
                </div>

                <div class="form-field form-group">
                    <label class="text-primary" for="msisdn">PHONE NUMBER: </label> <br>
                    <input name="msisdn" id="msisdn" type="tel" value="<?php echo env('USSD_PHONE', '') ?>"
                           placeholder="+..." autocomplete="on">

                    <small class="text-muted unknown-network-error" style="font-size:10px;display:none;"><br>Unable
                        to determine the network
                        of
                        this
                        number.<br>
                        Kindly select the
                        proper
                        network.</small>
                </div>

                <div class="form-field form-group">
                    <label class="text-primary" for="network">NETWORK MNC: </label> <br>
                    <input name="network" id="network" type="text" autocomplete="on">
                    <small class="text-muted unknown-network-error" style="font-size:10px;display:none;"><br>Network and
                        number mismatch</small>
                </div>

                <div class="form-field form-group" title="May be required by some  applications">
                    <label class="text-primary" for="ussdCode">USSD CODE: </label> <br>
                    <input name="ussdCode" id="ussdCode" class="ussdCode" type="text" pattern="\*\d{1,}(\*\d{1,})*#"
                           autocomplete="on" value="<?php echo env('USSD_CODE', '') ?>" />
                </div>

                <div class="form-field">
                    <small id="loading">Press "DIAL" to initiate a request.</small>
                </div>

                <div class="form-field form-group">
                    <button type="button" class="cancel">CANCEL</button>
                    <button class="send" type="submit">DIAL</button>
                </div>
            </form>
        </div>

        <div id="app" class="row">
            <div class="col-md">
                <div id="phone-bg">
                    <div id="phone-screen">
                        <div id="phone-time">
                            <div id="phone-watch"></div>
                            <div id="phone-date"></div>
                        </div>

                        <div id="ussd-loader">
                            <div class="lds-ring" id="loader-spinner">
                                <div></div>
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>
                            <div class="code-running" style="font-size: 13px">USSD code running</div>
                        </div>

                        <div id="dial-ussdCode">
                            <div id="dial-ussdCode-icon" class="send">&#x1F4DE;</div>
                            <div id="dial-ussdCode-tooltip" contenteditable></div>
                        </div>

                        <div id="ussd-popup">
                            <form>
                                <div id="ussd-popup-content"></div>
                                <div id="simulator-response-input-field">
                                    <input name="simulator-response-input" id="simulator-response-input" type="text"
                                           minLength="1" maxLength="160" title="Input a response" />
                                </div>
                                <div id="phone" class="ussd-popup-ctrl">
                                    <button type="button" class="cancel">CANCEL</button>
                                    <button class="send" type="submit">DIAL</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div id="simulator-debug" class="col-md">
                <div id="simulator-debug-content" class="card m-1">
                    <div class="card-header text-primary">RESPONSE PAYLOAD</div>
                    <div class="card-body">
                        <div class="card-text"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php $httpType = $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] ? 'http' : 'https'; ?>
    <div class="d-none" id="pageUrl"><?php echo $httpType.'://'.$_SERVER['HTTP_HOST']; ?></div>

    <div class="d-none" id="simulator-data"><?php echo $rawSimulatorData ?></div>

    <!-- Post parameters -->
    <div class="d-none" id="user-response-param-name">
        <?php echo env('REQUIRED_PARAM_NAME_USER_RESPONSE', 'ussdString') ?>
    </div>
    <div class="d-none" id="menu-string-param-name">
        <?php echo env('REQUIRED_PARAM_NAME_MENU_STRING', 'message') ?>
    </div>
    <div class="d-none" id="request-type-param-name">
        <?php echo env('REQUIRED_PARAM_NAME_REQUEST_TYPE', 'ussdServiceOp') ?>
    </div>
    <div class="d-none" id="session-id-param-name"><?php echo env('REQUIRED_PARAM_NAME_SESSION_ID', 'sessionID') ?>
    </div>
    <div class="d-none" id="user-phone-param-name"><?php echo env('REQUIRED_PARAM_NAME_USER_PHONE', 'msisdn') ?>
    </div>
    <div class="d-none" id="user-network-param-name"><?php echo env('REQUIRED_PARAM_NAME_USER_NETWORK', 'network') ?>
    </div>

    <!-- Request types -->
    <div class="d-none" id="ussd-request-init-code"><?php echo env('REQUEST_INIT_CODE', '1') ?>
    </div>
    <div class="d-none" id="ussd-request-end-code"><?php echo env('REQUEST_END_CODE', '17') ?>
    </div>
    <div class="d-none" id="ussd-request-failed-code"><?php echo env('REQUEST_FAILED_CODE', '3') ?>
    </div>
    <div class="d-none" id="ussd-request-cancelled-code"><?php echo env('REQUEST_CANCELLED_CODE', '30') ?>
    </div>
    <div class="d-none" id="ussd-request-ask-user-response-code">
        <?php echo env('REQUEST_ASK_USER_RESPONSE_CODE', '2') ?>
    </div>
    <div class="d-none" id="ussd-request-user-sent-response-code">
        <?php echo env('REQUEST_USER_SENT_RESPONSE_CODE', '18') ?>
    </div>

    <template class="simulator-debug-template">
        <div class="card">
            <div class="card-header"></div>
            <div class="card-body">
                <div class="card-text"></div>
            </div>
        </div>
    </template>

    <script src="src/public/js/jquery-3.1.0.min.js"></script>
    <script src="src/public/js/ussdsim.js"></script>
</body>

</html>