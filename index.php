<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;

$config = include('config.php');

session_start();
// resetSession();


$dataService = DataService::Configure(array(
    'auth_mode' => 'oauth2',
    'ClientID' => $config['client_id'],
    'ClientSecret' =>  $config['client_secret'],
    'RedirectURI' => $config['oauth_redirect_uri'],
    'scope' => $config['oauth_scope'],
    'baseUrl' => $config['base_url'] 
));
$OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
$authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();


// Store the url in PHP Session Object;
$_SESSION['authUrl'] = $authUrl;

//set the access token using the auth object
if (isset($_SESSION['sessionAccessToken'])) {
    
    $accessToken = $_SESSION['sessionAccessToken'];
   
    $accessTokenJson = array('token_type' => 'bearer',
        'access_token' => $accessToken->getAccessToken(),
        'refresh_token' => $accessToken->getRefreshToken(),
        'x_refresh_token_expires_in' => $accessToken->getRefreshTokenExpiresAt(),
        'expires_in' => $accessToken->getAccessTokenExpiresAt()
    );

    $realmId = $_SESSION['realm_id'];
    
    $dataService = DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => $config['client_id'],
        'ClientSecret' =>  $config['client_secret'],
        'RedirectURI' => $config['oauth_redirect_uri'],
        'scope' => $config['oauth_scope'],
        'accessTokenKey' => $accessToken->getAccessToken(),
        'refreshTokenKey' => $accessToken->getRefreshToken(),
        'QBORealmID' => $realmId,
        'baseUrl' => $config['base_url'],
    ));
    $dataService->updateOAuth2Token($accessToken);
    $oauthLoginHelper = $dataService -> getOAuth2LoginHelper();
}

function resetSession() {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_POST['reset_session'])) {
    resetSession();
}

?>

<!DOCTYPE html>
<html>
<head>
    <link rel="apple-touch-icon icon shortcut" type="image/png" href="https://plugin.intuitcdn.net/sbg-web-shell-ui/6.3.0/shell/harmony/images/QBOlogo.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="views/common.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <script>

        var url = '<?php echo $authUrl; ?>';

        var OAuthCode = function(url) {

            this.loginPopup = function (parameter) {
                this.loginPopupUri(parameter);
            }

            this.loginPopupUri = function (parameter) {

                // Launch Popup
                var parameters = "location=1,width=800,height=650";
                parameters += ",left=" + (screen.width - 800) / 2 + ",top=" + (screen.height - 650) / 2;

                var win = window.open(url, 'connectPopup', parameters);
                var pollOAuth = window.setInterval(function () {
                    try {

                        if (win.document.URL.indexOf("code") != -1) {
                            window.clearInterval(pollOAuth);
                            win.close();
                            location.reload();
                        }
                    } catch (e) {
                        console.log(e)
                    }
                }, 100);
            }
        }


        var apiCall = function() {
            this.getEstimates = function() {
                $('#apiCall').html('<div class="loading">Loading...</div>');
                $.ajax({
                    type: "GET",
                    url: "apiCall.php",
                }).done(function(msg) {
                    $('#apiCall').html(msg);
                }).fail(function(jqXHR, textStatus) {
                    $('#apiCall').html('<div class="error">Error: ' + textStatus + '</div>');
                });
            }

            this.refreshToken = function() {
                $.ajax({
                    type: "POST",
                    url: "refreshToken.php",
                }).done(function( msg ) {

                });
            }
        }

        var oauth = new OAuthCode(url);
        var apiCall = new apiCall();
    </script>
</head>
<body>

<div class="container">

    <h1>
        <a href="http://developer.intuit.com">
        <img src="assets/prodjex.png" id="headerLogo" style="width: 150px; height: auto; filter: brightness(0.8);">
        </a>

    </h1>

    <hr>

    <div class="well text-center">

        <h1>
            QuickBooks API & Airtable Integration 
            <?php 
                if ($config['base_url'] == 'production') {
                    echo "(Production)";
                } elseif ($config['base_url'] == 'development') {
                    echo "(Development)";
                }
            ?>
        </h1>
        <h2>OAuth2 Authentication, API Requests, and Airtable Synchronization</h2>

        <br>

    </div>

    <p>If there is no access token or the access token is invalid, click the <b>Connect to QuickBooks</b> button below.</p>
    <pre id="accessToken">
        <style="background-color:#efefef;overflow-x:scroll"><?php
    $displayString = isset($accessTokenJson) ? $accessTokenJson : "No Access Token Generated Yet";
    echo json_encode($displayString, JSON_PRETTY_PRINT); ?>
    </pre>
    <?php if ($displayString !== "No Access Token Generated Yet"): ?>
    <form method="post" style="display:inline;">
        <button type="submit" name="reset_session" class="btn btn-danger">Reset Session</button>
    </form>
    <?php endif; ?>

    <a class="imgLink" href="#" onclick="oauth.loginPopup()">
        <img src="assets/quickbooks.png" width="178" />
    </a>
    <hr />

    <h2>Sync Records from Quickbooks with Airtable Base</h2>
    <p>If there is no access token or the access token is invalid, click <b>Connect to QuickBooks</b> button above.</p>
    <pre id="apiCall"></pre>
    <button  type="button" class="btn btn-success" onclick="apiCall.getEstimates()">Sync Records</button>

    <hr />

</div>
</body>
</html>
