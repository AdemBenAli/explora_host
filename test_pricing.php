<?php
require __DIR__.'/vendor/autoload.php';
$kernel = new App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$t = $em->getRepository(App\Entity\Transport::class)->findOneBy(['numeroVol' => 'TUN001']);
if (!$t) { echo "Transport non trouvé."; exit; }

echo "Transport: " . $t->getId() . "\n";
echo "Type: " . $t->getType()->name . "\n";
echo "Capacity total according to service: " . (App\Service\DynamicPricingService::class ? 'Loaded' : '') . "\n";

$pricing = $container->get(App\Service\DynamicPricingService::class);
$res = $pricing->calculerPrix($t);
print_r($res);
