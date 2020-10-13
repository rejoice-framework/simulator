<?php

    $autoload = __DIR__.'/../../../autoload.php';

    if (!file_exists($autoload)) {
        $autoload = __DIR__.'/../../vendor/autoload.php';
    }

    require_once $autoload;

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
    $jsonFile = (realpath(__DIR__.'/../../../../../simulator.json') ?:
        realpath(__DIR__.'/../../simulator.json'));
    echo $jsonFile;

    if (file_exists($jsonFile)) {
        $data = json_decode(file_get_contents($jsonFile), true);
    }

    /*
    function checkNumberNameExists($name, $networks)
    {
    // This is to search for duplicate name and mnc when the user wants to add a
    // new network. Not efficient but cool if the data is not too much - I was
    // implementing an index-like system to quickly index the mnc and the name. So
    // that I can quickly check for duplicate but... infosevo is waiting for me. I
    // need to leave this.

    foreach ($networks as $networkData) {
    $testPhones = $networkData['test_phones'];

    foreach ($testPhones as $number => $value) {
    if ($value['name'] === $name) {
    return false;
    }
    }
    }

    return true;
    }

    function checkMncExists($mnc, $networks)
    {
    foreach ($networks as $networkData) {
    if ($networkData['mnc'] == $mnc) {
    return false;
    }
    }

    return true;
    }
     */

    $networks = $data['networks'] ?? [];

    if (isset($_POST['network'])) {
        $network = $_POST['network'];

        if (isset($_POST['delete-network'])) {
            if (isset($networks[$network])) {
                unset($networks[$network]);
            }

            $_SESSION['flash'] = 'Network deleted successfully';
            $_SESSION['network-already-deleted'] = $network;
            $data['networks'] = $networks;
            file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
            header('Location: '.$_SERVER['PHP_SELF']);
        } elseif (!isset($_POST['mnc'])) {
            $error = 'The mnc field is required';
        } else {
            $update = true;

            if (!isset($networks[$network])) {
                $update = false;
                $networks[$network] = [
                    'mnc'         => '',
                    'patterns'    => [],
                    'test_phones' => [],
                ];
            }

            $mnc = htmlspecialchars(strval($_POST['mnc']));

            $patterns = explode("\r\n", $_POST['patterns'] ?? '');
            $networks[$network]['patterns'] = $patterns;
            $networks[$network]['mnc'] = $mnc;

            if (!isset($data['mnc_index'])) {
                $data['mnc_index'] = [];
            }

            $_SESSION['new-network'] = $network;
            $_SESSION['flash'] = 'Network '.($update ? 'updated' : 'added').' successfully';

            $data['networks'] = $networks;
            file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
            header('Location: '.$_SERVER['PHP_SELF']);
        }
    } elseif (isset($_POST['network'])) {
        $error = 'The phone input is required';
    } elseif (isset($_POST['delete-number']) ||
        isset($_POST['number']) ||
        isset($_POST['network'])
    ) {
        $error = 'Cannot delete this network.';
    } elseif (isset($_GET['network'])) {
        $network = htmlspecialchars($_GET['network']);

        if (isset($networks[$network])) {
            $networkName = $network;
            $mnc = $networks[$network]['mnc'];
            $patterns = implode("\r\n", $networks[$network]['patterns']);
        } elseif (
            !(isset($_SESSION['network-already-deleted']) &&
                $_SESSION['network-already-deleted'] == $network)
        ) {
            $error = 'Network "'.$network.'" not found';
        }
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
        <div class="m-2 ml-4" style="display:inline-block;"><a href="../../" class="ml-4">Simulator</a> </div>
        <div class="m-2" style="display:inline-block;"><a href="phone.php">Manage phones</a></div>
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

        <div class="row justify-content-center">
            <div class="col-md-6 my-3">
                <h3>Add new network</h3>
                <form method="POST" action="">
                    <div class="form-field form-group mt-2">
                        <label class="text-primary" for="network">Network name<sup><small class="text-secondary">*</small></sup></label>
                        <input type="text" name="network" id="network" class="form-control" placeholder="Network name" value="<?php echo $networkName ?? '' ?>" autofocus required>
                    </div>

                    <div class="form-field form-group" title="MNC">
                        <label class="text-primary" for="mnc">MNC<sup><small class="text-secondary">*</small></sup></label>
                        <input type="text" name="mnc" id="mnc" input="numeric" class="form-control" pattern="[0-9]{1,3}" value="<?php echo $mnc ?? '' ?>" placeholder="MNC">
                    </div>

                    <div class="form-field form-group">
                        <label class="text-primary" for="network">Phone number patterns for this network <small>(one per
                                line)</small></label><br>
                        <textarea class="form-control" name="patterns" id="patterns" rows="3" placeholder="The pattern is used to automatically determine the network a number belongs to this network when you will be adding new numbers"><?php echo $patterns ?? '' ?></textarea>
                    </div>
                    <div class="form-field">
                        <button class="send" type="submit">Save network</button>
                    </div>
                </form>
            </div>
            <div class="offset-md-1 col-md-5 my-3">
                <h3 class=""> Saved networks</h3>
                <small class="text-muted">Click on a network to edit it</small>
                <?php foreach ($networks as $networkName => $networkData) {
            ?>
                <div class="card my-2 rounded-0 border-top-0 network-container">
                    <div class="card-header row">
                        <div class="text-primary col-6" title="Modify this network"><a href="?network=<?php echo $networkName ?>"><?php echo $networkName ?></a>
                        </div>
                        <div class="col-4">
                            mnc: <?php echo htmlspecialchars($networkData['mnc']) ?? 'MNC not defined' ?>
                        </div>
                        <div class="col-2">
                            <form method="POST" action="" title="Delete this network" class="delete-network">
                                <input type="hidden" name="delete-network" value="1">
                                <input type="hidden" name="network" value="<?php echo $networkName ?>">
                                <input type="submit" class="btn btn-sm btn-danger" value="x">
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title network-mnc">Phone patterns</h6>
                        <div class="card-text row">
                            <pre><?php echo htmlspecialchars(implode("\r\n", $networkData['patterns'])) ?></pre>
                        </div>
                    </div>
                </div>
                <?php
        }?>
            </div>
        </div>

    </main>
    <script src="../public/js/jquery-3.1.0.min.js"></script>
    <script>
    $(document).ready(() => {
        $('.delete-network').on('submit', function(event) {
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
