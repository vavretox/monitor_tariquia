<?php

namespace Tests\Unit;

use App\Models\AccionEjecucion;
use App\Models\AccionObservacion;
use App\Models\User;
use App\Notifications\ActionObservationNotification;
use PHPUnit\Framework\TestCase;

class ActionObservationNotificationTest extends TestCase
{
    public function test_notification_preserves_message_and_identifies_the_reply(): void
    {
        $action = new AccionEjecucion();
        $action->setRawAttributes(['id' => 7, 'titulo' => 'Reparar sistema']);
        $observation = new AccionObservacion();
        $observation->setRawAttributes(['id' => 9, 'parent_id' => 2, 'mensaje' => '<script>texto</script>']);
        $user = new User();
        $user->setRawAttributes(['name' => 'Autor']);
        $observation->setRelation('usuario', $user);
        $notification = new ActionObservationNotification($action, $observation);
        $this->assertSame(['database'], $notification->via($user));
        $this->assertSame([
            'accion_id' => 7, 'titulo' => 'Reparar sistema', 'mensaje' => '<script>texto</script>',
            'autor' => 'Autor', 'observacion_id' => 9, 'respuesta' => true,
        ], $notification->toArray($user));
    }
}
