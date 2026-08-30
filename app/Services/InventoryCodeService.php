<?php

namespace App\Services;

use PDO;
use RuntimeException;

final class InventoryCodeService
{
    public function next(PDO $db, string $entity, string $prefix): string
    {
        if (!$db->inTransaction()) throw new RuntimeException('A sequencia de estoque exige uma transacao ativa.');
        $statement=$db->prepare('select current_value from inventory_sequences where entity=:entity for update');
        $statement->execute(['entity'=>$entity]);
        $current=$statement->fetchColumn();
        if($current===false)throw new RuntimeException('Sequencia de estoque nao configurada. Execute as migracoes.');
        $next=(int)$current+1;
        $db->prepare('update inventory_sequences set current_value=:value where entity=:entity')->execute(['value'=>$next,'entity'=>$entity]);
        return $prefix.'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT);
    }
}
