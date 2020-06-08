<?php
namespace Prinx\Simulator;

require_once __DIR__ . '/../../../autoload.php';
use function Prinx\Dotenv\env;
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>USSD SIMULATOR</title>

    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/style.css">
</head>

<!--
Do not remove the inline styles, unless you've got a way to do what the are
doing without using an inline style.
These CSS are just here to force the body to have always it initial style.
Those here are purposely to prevent the modification of the body's font
property when a PHP error occured at the server side (When an error occured,
the error is returned by PHP in an HTML formatted way, thus it comes as a whole
HTML page on itself, meaning it come with a title, a meta tag and CSS styles,
etc. The CSS styles verride the default CSS of the page resulting in unwanted
look of the page.)
-->

<body style="line-height: normal; font-size:initial; font-family: initial; color:initial">
    <header>
        <div class="toggle-controls" title="Controls">&Congruent;</div>
    </header>

    <main class="container">
        <div id="controls" class="controls-hidden">
            <form>
                <div class="form-field">
                    <label class="text-primary" for="shortcode">SHORTCODE: </label> <br>
                    <input name="shortcode" id="shortcode" type="text" pattern="\*\d{1,}(\*\d{1,})*#" autocomplete="on"
                           value="<?php echo env('SHORTCODE') ?>" />
                </div>
                <div class="form-field">
                    <label class="text-primary" for="endpoint">ENDPOINT: </label> <br>
                    <input name="endpoint" id="endpoint" type="url" autofocus autocomplete="on"
                           value="<?php echo env('APP_URL') ?>" />
                </div>
                <div class="form-field">
                    <label class="text-primary" for="msisdn">MSISDN: </label> <br>
                    <select name="msisdn" id="msisdn" autocomplete="on">
                        <option value="233545466796">PRINCE MTN</option>
                        <option value="233204038261">MIKE MTN</option>
                        <option value="233242245046">RAZAK MTN</option>
                        <option value="+233200822158">+233200822158(test phone)</option>
                        <option value="+233268652437">+233268652437(test phone)</option>
                        <option value="233549143481">233549143481</option>
                        <option value="+233200821963">+233200821963(test phone)</option>
                        <option value="+233504940867">+233504940867</option>
                        <option value="+233200820280">+233200820280</option>
                    </select>
                </div>

                <div class="form-field">
                    <label class="text-primary" for="network">NETWORK: </label> <br>
                    <select name="network" id="network">
                        <option value="01">MTN</option>
                        <option value="07">GLO</option>
                        <option value="03">TIGO</option>
                        <option value="02">VODAFONE</option>
                        <option value="03">AIRTEL</option>
                    </select>
                </div>
                <div class="form-field">
                    <small id="loading">Press "DIAL" to initiate a request.</small>
                </div>

                <div class="form-field">
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
                            <div style="font-size: 13px">Processing USSD...</div>
                        </div>

                        <div id="dial-shortcode">
                            <div id="dial-shortcode-icon" class="send">&#x1F4DE;</div>
                            <div id="dial-shortcode-tooltip" contenteditable>*380*75#</div>
                        </div>

                        <div id="ussd-popup">
                            <form>
                                <div id="ussd-popup-content"></div>
                                <div id="simulator-response-input-field">
                                    <input name="simulator-response-input" id="simulator-response-input" type="text" />
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
                <div class="text-primary">RESPONSE:</div>
                <div id="simulator-debug-content"></div>
                <div id="simulator-warning-content"></div>
                <div id="simulator-info-content"></div>
            </div>
        </div>
    </main>
    <script src="js/jquery-3.1.0.min.js"></script>
    <!-- <script src="js/chrome-request-pending-handler.js"></script> -->
    <script src="js/ussdsim.js"></script>
</body>

</html>
