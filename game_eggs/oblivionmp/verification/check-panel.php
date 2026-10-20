<?php
require '/api/vendor/autoload.php';
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as FactoryContract;
use Illuminate\Validation\Factory;
use Illuminate\Translation\Translator;
use Illuminate\Translation\ArrayLoader;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Eggs\EggConfigurationService;
use Pterodactyl\Services\Eggs\EggParserService;
use Pterodactyl\Services\Files\StarredFilePathService;
$factory = new Factory(new Translator(new ArrayLoader(), 'en'));
Container::getInstance()->instance(FactoryContract::class, $factory);
$egg = json_decode(file_get_contents('/egg/egg-oblivionmp.json'), true, 512, JSON_THROW_ON_ERROR);
$parser = new EggParserService(new StarredFilePathService());
$parsed = $parser->handleArray($egg);
$model = $parser->fillFromParsed(new Egg(), $parsed);
if ($model->startup !== 'exec ./server') throw new Exception('Startup mismatch');
$server = new Server();
$vars = [];
$env = [];
foreach ($egg['variables'] as $v) {
    $factory->make(['value'=>$v['default_value']], ['value'=>$v['rules']])->validate();
    $vars[] = (new EggVariable())->forceFill($v);
    $env[$v['env_variable']] = $v['default_value'];
}
$env['SERVER_NAME'] = 'Quotes " / slash \\ $HOME `echo nope`';
$env['SERVER_PASSWORD'] = 'secret " \\ $(echo nope)';
$env['LISTING_ENABLED'] = 'true';
$env['TELEMETRY_OPT_OUT'] = 'true';
$env['PUBLIC_HOST'] = 'example.com';
$server->setRelation('variables', new Illuminate\Database\Eloquent\Collection($vars));
$reflection = new ReflectionClass(EggConfigurationService::class);
$service = $reflection->newInstanceWithoutConstructor();
$method = $reflection->getMethod('matchAndReplaceKeys');
$method->setAccessible(true);
$find = json_decode($egg['config']['files'], true)['config.json']['find'];
$result = [];
foreach ($find as $key=>$value) {
    $result[$key] = $method->invoke($service, $server, $value, ['build'=>['env'=>$env, 'default'=>['port'=>19050]]]);
}
if ($result['ServerList.Enabled'] !== true || $result['Telemetry.OptOut'] !== true || $result['ServerList.MaxPlayers'] !== 8) throw new Exception('Typed replacements failed');
if ($result['Server.Password'] !== $env['SERVER_PASSWORD'] || $result['ServerList.Name'] !== $env['SERVER_NAME']) throw new Exception('Escaping failed');
if ((int)$result['Server.Port'] !== 19050 || (int)$result['ServerList.PublicPort'] !== 19050) throw new Exception('Allocation port failed');
$stop = $reflection->getMethod('convertStopToNewFormat');
$stop->setAccessible(true);
if ($stop->invoke($service, $egg['config']['stop']) !== ['type'=>'signal', 'value'=>'SIGINT']) throw new Exception('Stop signal conversion failed');
$slotRules = array_column($egg['variables'], 'rules', 'env_variable')['MAX_PLAYERS'];
foreach ([0,65] as $n) {
 if (!$factory->make(['value'=>$n], ['value'=>$slotRules])->fails()) throw new Exception('Bad slot count accepted');
}
echo json_encode(['result'=>'PASS','checks'=>['PTDL import parsing','model fill','all variable defaults','boolean/integer conversion','quoted names/passwords','allocation ports','SIGINT conversion','slot bounds']], JSON_PRETTY_PRINT)."\n";
