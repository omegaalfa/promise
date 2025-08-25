<?php

require_once 'vendor/autoload.php';

async(function ($resolve) {
    $resolve("ok");
})
    ->then(fn($v) => strtoupper($v)) // encadeia transformação
    ->then(fn($v) => print "Valor final: $v\n")
    ->catch(fn($err) => print "Erro: $err\n");

// Promise rejeitada
async(function ($resolve, $reject) {
    $reject("falhou");
})
    ->then(fn($v) => print "Nunca chega aqui\n")
    ->catch(fn($err) => print "Erro tratado: $err\n");


async(static function ($resolve) {
    return $resolve('success');
})->then(function ($value) {
    echo $value . PHP_EOL;
})->catch(function ($reason) {
    echo 'Error: ' . $reason . PHP_EOL;
});

async(function ($resolve, $reject) {
    // Simula operação assíncrona (ex: consulta API ou leitura de arquivo)
    sleep(1);
    $resolve("Operação concluída com sucesso!");
})
    ->then(function ($result) {
        echo "✔ THEN: $result\n";
    })
    ->catch(function ($error) {
        echo "❌ CATCH: " . $error->getMessage() . "\n";
    })
    ->finally(function () {
        echo "🎯 FINALLY sempre executa!\n";
    });
