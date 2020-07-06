<?php
/**
 * Make we play small! 🤓
 */
// error_reporting(E_ALL);
require_once __DIR__ . '/../../../../autoload.php';
session_start();

$error = null;
$flash = null;

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

if (isset($_SESSION['new-phone'])) {
    $newPhone = $_SESSION['new-phone'];
    unset($_SESSION['new-phone']);
}

$data = [];
$jsonFile = realpath(__DIR__ . '/../../../../../') . '/simulator.json';

if (file_exists($jsonFile)) {
    $data = json_decode(file_get_contents($jsonFile), true);
}

$networks = $data['networks'] ?? [];

function guessNetwork($number, $networks)
{
    foreach ($networks as $networkName => $networkData) {
        if (isset($networkData['patterns'])) {
            foreach ($networkData['patterns'] as $pattern) {
                if (preg_match('/' . $pattern . '/', $number)) {
                    return $networkName;
                }
            }
        }
    }

    return false;
}

if (isset($_POST['number'])) {

    $network = $_POST['network'] ?? guessNetwork($_POST['number'], $networks);

    if (!$network) {
        $error = "Unable to guess the network";
    } elseif (!isset($networks[$network])) {
        $error = 'The network specified (' . $network . ') does not exist. Kindly create the network <a href="network.php">here</a>';
    } elseif (isset($_POST['delete-number'])) {
        if (isset($networks[$network]['test_phones'][$_POST['number']])) {
            unset($networks[$network]['test_phones'][$_POST['number']]);
        }

        $_SESSION['flash'] = "Number deleted successfully";
        $data['networks'] = $networks;
        file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
        header('Location: ');
    } else {
        $number = preg_replace('/[^0-9]/', '', $_POST['number']);

        if (!isset($networks[$network]['test_phones'])) {
            $networks[$network]['test_phones'] = [];
        }

        $update = false;

        if (isset($networks[$network]['test_phones'][$number])) {
            $update = true;
        }

        $networks[$network]['test_phones'][$number] = [
            'name' => $_POST['phone-name'] ?? '',
        ];

        $_SESSION['new-phone'] = $number;
        $_SESSION['flash'] = "Number " . ($update ? "updated" : "added") . " successfully";

        $data['networks'] = $networks;
        file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
        header('Location: ');
    }
} elseif (isset($_POST['network'])) {
    $error = 'The phone input is required';
} elseif (isset($_POST['delete-number']) ||
    isset($_POST['number']) ||
    isset($_POST['network'])
) {
    $error = "Cannot delete this number.";
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>USSD SIMULATOR</title>

    <link rel="stylesheet" href="../public/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
    .phone-number-row {
        cursor: pointer;
    }

    </style>
</head>

<body style="line-height: normal; font-size:initial; font-family: initial; color:initial">
    <header>
        <div class="toggle-controls" title="Controls">&Congruent;</div>
        <div class="m-2 ml-4" style="display:inline-block;"><a href="/" class="ml-4">Simulator</a> </div>
        <div class="m-2" style="display:inline-block;"><a href="network.php">Manage Networks</a></div>
    </header>

    <main class="container">
        <?php
if ($error) {?>
        <div class="alert alert-danger"><?php echo $error ?></div>
        <?php
}

if ($flash) {?>
        <div class="alert alert-success alert-dismissible fade show not-static-alert" role="alert">
            <!-- <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                <span class="sr-only">Close</span>
            </button> -->
            <?php echo $flash; ?>
        </div>
        <?php
}?>

        <?php
if ($networks) {
    ?>
        <div class="row justify-content-center">
            <div class="col-md-6 my-3">
                <h3>Add new test phone</h3>

                <form method="POST" action="">
                    <div class="form-field form-group mt-2">
                        <label class="text-primary" for="number">Phone number</label>
                        <input type="tel" name="number" id="number" class="form-control" placeholder="Phone number"
                               pattern="\+?[0-9)( -]{7,15}" autofocus required>
                    </div>

                    <div class="form-field form-group"
                         title="The person the phone belongs to or what you want to test the phone with">
                        <label class="text-primary" for="phone-name">Name</label>
                        <input type="text" name="phone-name" id="phone-name" class="form-control"
                               placeholder="Give a name to the number">
                    </div>

                    <div class="form-field form-group">
                        <label class="text-primary" for="network">Network</label><br>
                        <select class="custom-select" name="network" id="network">
                            <option title="Leave blank to let the application guess automatically the network. It can work only if you have configured the patterns on the networks page"
                                    disabled selected>
                                Leave blank to let the application guess
                            </option>

                            <?php
foreach ($networks as $networkName => $networkData) {?>
                            <option value="<?php echo $networkName ?>">
                                <?php echo $networkName ?>
                            </option>
                            <?php
}?>
                        </select>
                    </div>
                    <div class="form-field">
                        <button class="send" type="submit">Save number</button>
                    </div>
                </form>
            </div>
            <div class="offset-md-1 col-md-5 my-3">
                <h3 class=""> Saved phones numbers</h3>
                <small class="text-muted">Click on a phone number to edit it</small>
                <?php foreach ($networks as $networkName => $networkData) {
        ?>
                <div class="card my-2 rounded-0 border-top-0">
                    <div class="card-header row">
                        <div class="text-primary col-8" title="Modify this network"><a
                               href="network.php?network=<?php echo $networkName ?>"><?php echo $networkName ?></a>
                        </div>

                        <!-- <div class="col">
                            mnc: <?php echo $networkData['mnc'] ?? 'MNC not defined' ?>
                        </div> -->
                    </div>
                    <div class="card-body">
                        <!-- <h6 class="card-title network-mnc">Numbers</h6> -->
                        <div class="card-text row justify-content-center">
                            <div>
                                <?php $phones = $networkData['test_phones'] ?? [];if (!$phones) {?>
                                <i>No phone number added here.</i>
                                <?php } else {
            ?>
                                <table class="bg-white table table-responsive table-hover">
                                    <tbody>
                                        <?php foreach ($phones as $number => $phoneData) {
                if ($number) {
                    ?>
                                        <tr data-network="<?php echo $networkName ?>" title="Click to edit"
                                            class="phone-number-row <?php if (isset($newPhone) && $newPhone == $number/* Do not use strict comparison here */) {echo 'new-phone';}?>">
                                            <td scope="row" class="phone-number">
                                                <?php echo $number ?>
                                            </td>
                                            <td class="phone-name">
                                                <?php echo ($phoneData['name'] ?? '') ?: 'No name' ?>
                                            </td>
                                            <td class="delete-phone-number">
                                                <form method="POST" action="" class="delete-number"
                                                      title="Delete this number">
                                                    <input type="hidden" name="delete-number" value="1">
                                                    <input type="hidden" name="number" value="<?php echo $number ?>">
                                                    <input type="hidden" name="network"
                                                           value="<?php echo $networkName ?>">

                                                    <input type="submit" class="btn btn-sm btn-danger delete-number-btn"
                                                           value="x">
                                                </form>
                                            </td>
                                        </tr>
                                        <?php }}?>
                                    </tbody>
                                </table>
                                <?php }?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php }?>
            </div>
        </div>
        <?php } else {?>
        <div class="alert alert-info">No network not defined. Every number must belong to a previously defined
            network.
            Kindly
            defined the networks <a href="network.php">here</a>.
        </div>

        <?php }?>

    </main>
    <script src="../public/js/jquery-3.1.0.min.js"></script>
    <script src="../public/js/add-phone.js"></script>
    <script>
    $(document).ready(() => {
        $('.new-phone').addClass('alert-success')
        console.log($('.new-phone'))
        setTimeout(() => {
            $('.new-phone').removeClass('alert-success')
            $('.new-phone').removeClass('new-phone')
        }, 5000);

        $('.phone-number-row').on('click', function(event) {
            if ($(event.target).hasClass('delete-number-btn')) {
                return;
            }

            const elmt = $(this);
            console.log('elmt', elmt)
            console.log('event', event)
            console.log('this', this)
            const number = elmt.find('.phone-number').text().trim()
            $('#number').val(number)

            const name = elmt.find('.phone-name').text().trim()

            $('#phone-name').val(name)
            const network = this.dataset.network
            $('#network').val(network)

            if ($(event.target).hasClass('phone-number')) {
                $('#number').focus()
            } else {
                $('#phone-name').focus()
            }
        })

        $('.delete-number').on('submit', function(event) {
            event.preventDefault()

            if (confirm('Do you really want to delete this number ?')) {
                this.submit()
            }
        })

        setTimeout(() => {
            $('.not-static-alert').hide(250)
        }, 10000);
    })
    </script>

</body>

</html>
