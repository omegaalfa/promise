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

use Omegaalfa\Promise;

function someAsyncOperation(): string
{
	return "Operation completed successfully!";
}

// Simula uma operação assíncrona que falha
function someFailingOperation(): void
{
	throw new RuntimeException("Operation failed!");
}

## Exemplo básico de uso
$promise = new Promise(function($resolve, $reject) {
	try {
		$result = someAsyncOperation();
		$resolve($result);
	} catch(Throwable $e) {
		$reject($e);
	}
});

$promise
	->then(function($result) {
		echo "Success: $result\n";
	})
	->catch(function($error) {
		echo "Error: {$error->getMessage()}\n";
	})
	->finally(function() {
		echo "Completed\n";
	});

## Exemplo com Promise que falha
$promise = new Promise(function($resolve, $reject) {
	try {
		someFailingOperation();
	} catch(Throwable $e) {
		$reject($e);
	}
});

$promise
	->then(function($result) {
		echo "Success: $result\n";
	})
	->catch(function($error) {
		echo "Error: {$error->getMessage()}\n";
	})
	->finally(function() {
		echo "Completed\n";
	});

## Exemplo de Promise::all
Promise::all([
	new Promise(function($resolve) {
		$resolve("Result 1");
	}),
	new Promise(function($resolve) {
		$resolve("Result 2");
	}),
	new Promise(function($resolve) {
		$resolve("Result 3");
	}),
])->then(function($results) {
	echo "All results: " . implode(", ", $results) . "\n";
})->catch(function($error) {
	echo "Error: {$error->getMessage()}\n";
})->finally(function() {
	echo "All operations completed\n";
});

## Exemplo de Promise::race
Promise::race([
	new Promise(function($resolve) {
		sleep(1); // Simula demora
		$resolve("Result from slow operation");
	}),
	new Promise(function($resolve) {
		$resolve("Result from fast operation");
	}),
])->then(function($firstResult) {
	echo "First resolved result: $firstResult\n";
})->catch(function($error) {
	echo "Error: {$error->getMessage()}\n";
})->finally(function() {
	echo "Race completed\n";
});

## Usando `any` para aguardar qualquer promessa resolvida

$promise1 = new Promise(function($resolve, $reject) {
	// Simula uma operação assíncrona
	sleep(1);
	$resolve("Resultado 1");
});

$promise2 = new Promise(function($resolve, $reject) {
	// Simula uma operação assíncrona
	sleep(1);
	$resolve("Resultado 2");
});

$promise3 = new Promise(function($resolve, $reject) {
	// Simula uma operação assíncrona
	sleep(1);
	$reject(new Exception("Erro no Promise 3"));
});


Promise::any([$promise1, $promise2, $promise3])->then(
	function($value) {
		echo "Alguma promessa foi resolvida: $value"; // Resultado da primeira promessa resolvida.
	},
	function($reason) {
		echo "Todas as promessas foram rejeitadas: $reason"; // Se todas as promessas forem rejeitadas.
	}
);

```

## Contribuição

Se desejar contribuir com melhorias ou correções, fique à vontade para criar uma pull request ou abrir uma issue no repositório.

## Licença

Este projeto está licenciado sob a Licença MIT.
