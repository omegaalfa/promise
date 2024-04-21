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
use src\promises\Promise;

function fetchDataFromApi(): Promise {
    $promise = new Promise();
    // Simula uma chamada à API que demora 2 segundos
    usleep(2000000); // 2 seconds
    $promise->resolve('Received data from API');
    return $promise;
}

fetchDataFromApi()
    ->then(function ($data) {
        echo "Received data: $data\n";
    })
    ->then(function () {
        echo "Data processing completed\n";
    })
    ->catch(function ($error) {
        echo "Error: $error\n";
    });

```

## Contribuição

Se desejar contribuir com melhorias ou correções, fique à vontade para criar uma pull request ou abrir uma issue no repositório.

## Licença

Este projeto está licenciado sob a Licença MIT.
