<?php

require_once 'vendor/autoload.php';

async(function ($resolve, $reject) {
    $resolve("Hello, World!");
})->then(function ($result) {
    echo "Resultado: $result\n"; // deve imprimir "Resultado: Hello, World!"
});


async(function ($resolve, $reject) {
    $reject(new RuntimeException("Falhou aqui"));
})
    ->then(function ($result) {
        echo "Isso não será chamado\n";
    })
    ->catch(function ($error) {
        echo "Erro capturado: " . $error->getMessage() . "\n"; // imprime "Erro capturado: Falhou aqui"
    });

async(function ($resolve, $reject) {
    $resolve(5);
})
    ->then(function ($n) {
        return $n * 2;
    })
    ->then(function ($n) {
        return $n + 3;
    })
    ->then(function ($n) {
        echo "Encadeamento final: $n\n"; // deve imprimir "Encadeamento final: 13"
    });


async(function ($resolve, $reject) {
    // Simula operação assíncrona (ex: consulta API ou leitura de arquivo)
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
