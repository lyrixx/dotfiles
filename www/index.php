<?php

require __DIR__.'/vendor/autoload.php';

$finder = new Symfony\Component\Finder\Finder();

$finder
    ->in(getenv('HOME').'/dev')
    ->directories()
    ->ignoreVCS(true)
    ->ignoreDotFiles(true)
    ->depth('==1')
;

$dirs = array();
foreach ($finder as $dir) {
    if (file_exists($dir.'/web')) {
        list($dir, $project) = explode('/', $dir->getRelativePathname());
        if (!isset($dirs[$dir])) {
            $dirs[$dir] = array();
        }

        $dirs[$dir][$project] = sprintf('http://%s.%s.localhost', $project, $dir);
    }
}

$utils = array(
    'phpinfo' => '/phpinfo.php',
    'RabbitMq' => 'http://192.168.2.117:15672/'
)
?>
<html>
    <head>
        <title>Greg - localhost</title>
        <style type="text/css">
        </style>
    </head>
    <body>
        <h1>Greg - localhost</h1>
        <h2>Utils</h2>
        <ul>
            <?php foreach ($utils as $name => $link) : ?>
                <li>
                    <a href="<?php echo $link ?>">
                        <?php echo ucfirst(strtolower($name)) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <h2>Projects</h2>
        <ul>
            <?php foreach ($dirs as $dirName => $projects) : ?>
                <li>
                    <h3>
                        <?php echo ucfirst(strtolower($dirName)) ?>
                    </h3>
                    <ul>
                        <?php foreach ($projects as $projetName => $link): ?>
                            <li>
                                <a href="<?php echo $link ?>">
                                    <?php echo ucfirst(strtolower($projetName)) ?>
                                </a>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>
    </body>
</html>
