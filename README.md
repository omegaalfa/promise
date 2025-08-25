Promise
=======

 Uma implementação de promessas em PHP

Descrição
-----------

Essa classe `Promise` é uma implementação de promessas em PHP, que permite lidar com operações assíncronas de forma mais fácil e segura.

Características
--------------

*   Suporte a callbacks para lidar com resultados de operações assíncronas
*   Estado da promessa é gerenciado internamente (pending, fulfilled, rejected)
*   Possibilidade de encadear múltiplos callbacks com `then`
*   Suporte a erro handling com `catch`

Exemplo de uso
---------------

```php

require __DIR__ . '/vendor/autoload.php';

// Exemplo de uso
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

```

## Contribuição

Se desejar contribuir com melhorias ou correções, fique à vontade para criar um pull request ou abrir uma issue no repositório.

## Licença

Este projeto está licenciado sob a Licença MIT.
