<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<?php
echo "Let's begin!\n";

echo 'param 1 ' . $argv[1] . PHP_EOL;
echo 'param 2 ' . $argv[2] . PHP_EOL;
echo 'param 3 ' . $argv[3] . PHP_EOL;
echo "param count: ".count($argv)."\n";

for ($i = 1; $i < count($argv); $i++) {
    echo "param $i: ".$argv[$i]."\n";
}

?>

</body>
</html>
