<?php
namespace Database\Seeders;
use App\Models\Comunidad; use Illuminate\Database\Seeder;
class ComunidadSeeder extends Seeder {public function run():void{
$osm='OpenStreetMap / GeoNames';$u='Estudio UAJMS (UTM convertido)';
$r=[
['San José','Tariquía','Salud, energía, accesibilidad y servicios básicos.',-22.02917,-64.50552,$osm,'https://www.openstreetmap.org/node/5058939089',null],
['Acherales','Tariquía','Caminos, agua, energía y conectividad territorial.',-22.04657,-64.51949,$osm,'https://www.openstreetmap.org/node/5058939098',null],
['Puesto Rueda','Tariquía','Servicios básicos y regularización del suministro eléctrico.',-22.02007,-64.46797,$osm,'https://www.openstreetmap.org/node/5058939078',null],
['Volcán Blanco','Tariquía','Salud, educación, energía y conectividad.',-21.9649542,-64.4266867,'GeoNames','https://www.geonames.org/search.html?q=Volcan+Blanco&country=BO',null],
['Motoví','Tariquía','Salud, producción, semillas, energía y caminos.',-22.0506564,-64.3683332,$u,'https://biblioteca.uajms.edu.bo/biblioteca/opac_css/doc_num.php?explnum_id=35587','UTM X 358800, Y 7560933.'],
['San Pedro','Tariquía','Acceso y conectividad vial.',-22.04499,-64.39769,$osm,'https://www.openstreetmap.org/search?query=San%20Pedro%20Tariquia',null],
['Chillaguatas','Tariquía','Caminos, energía y conectividad territorial.',-22.07678,-64.40327,$osm,'https://www.openstreetmap.org/node/5058939088','En cartografía: Chillahuatas.'],
['Acheralitos','Tariquía','Acceso, agua, energía y servicios básicos.',-22.1139,-64.4441,$osm,'https://www.openstreetmap.org/search?query=Acheralitos%20Tarija',null],
['Pampa Grande','Tariquía','Salud, educación, agua, producción y turismo.',-22.0468259,-64.4358969,$u,'https://biblioteca.uajms.edu.bo/biblioteca/opac_css/doc_num.php?explnum_id=35587','UTM X 351823, Y 7561293.'],
['Tipas','Chiquiacá - Salinas','Conectividad territorial, servicios básicos y fortalecimiento productivo.',-21.8163,-64.4109,'Tipas Timboy / cartografía abierta','https://www.openstreetmap.org/?mlat=-21.8163&mlon=-64.4109#map=15/-21.8163/-64.4109','En cartografía: Tipas Timboy.'],
['Chiquiacá Norte','Chiquiacá - Salinas','Caminos, agua, producción agropecuaria y servicios comunitarios.',-21.82361,-64.11298,$osm,'https://www.openstreetmap.org/node/5059122737',null],
['Salinas','Chiquiacá - Salinas','Salud, educación, agua, energía, producción y turismo comunitario.',-21.7867,-64.2334,$osm,'https://www.openstreetmap.org/search?query=Salinas%20Tarija%20Bolivia',null],
['Loma Alta','Chiquiacá - Salinas','Agua, energía, conectividad y servicios básicos comunitarios.',-21.94703,-64.15089,$osm,'https://www.openstreetmap.org/node/5059120485',null],
['Pampa Redonda','Chiquiacá - Salinas','Agua, educación, transporte, energía y acceso territorial.',-21.9947,-64.1747,$osm,'https://www.openstreetmap.org/node/5059120514',null],
['Chajllas','Chiquiacá - Salinas','Caminos, educación, conectividad y servicios básicos.',-22.06095,-64.20063,$osm,'https://www.openstreetmap.org/node/5059120497','En cartografía: Chajlla.'],
['Río Conchas','La Planchada - El Cajón','Conectividad territorial, acceso vial y atención por aislamiento.',-22.367025,-64.411831,'Proyecto técnico / OSM','https://www.openstreetmap.org/?mlat=-22.36699&mlon=-64.41143#map=16/-22.36699/-64.41143',null],
['Piedra Grande','La Planchada - El Cajón','Acceso, conectividad, servicios básicos y fortalecimiento productivo.',-22.34657,-64.11385,$osm,'https://www.openstreetmap.org/node/5058939063','La fuente registra Piedra Grande El Cajón.'],
['El Cajón','La Planchada - El Cajón','Acceso territorial, servicios básicos, producción y articulación comunitaria.',-22.34657,-64.11385,$osm,'https://www.openstreetmap.org/node/5058939063','Comparte el punto Piedra Grande El Cajón.'],
['La Planchada','La Planchada - El Cajón','Accesibilidad vial, servicios básicos y fortalecimiento productivo.',-22.29411,-64.3892,$osm,'https://www.openstreetmap.org/node/5058939058',null],
['La Misión','Chiquiacá - Salinas',null,-21.79269,-64.23607,$osm,'https://www.openstreetmap.org/search?query=La%20Mision%20Salinas%20Tarija','Sin detalle de necesidades en el PDF.'],
['Chiquiacá Sur','Chiquiacá - Salinas',null,-21.90818,-64.12411,$osm,'https://www.openstreetmap.org/node/8597139931','Sin detalle de necesidades en el PDF.'],
['Chiquiacá Centro','Chiquiacá - Salinas',null,-21.86434,-64.12252,$osm,'https://www.openstreetmap.org/node/3226265073','Sin detalle de necesidades en el PDF.']];
foreach($r as [$nombre,$territorio,$necesidades,$latitud,$longitud,$fuente_geografica,$url_fuente,$observacion_geografica])Comunidad::updateOrCreate(['nombre'=>$nombre],compact('territorio','necesidades','latitud','longitud','fuente_geografica','url_fuente','observacion_geografica'));
}}