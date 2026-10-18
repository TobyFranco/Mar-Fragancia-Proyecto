<x-tienda.layout title="Cómo funciona">
    <div class="page-width how-page">
        <div class="how-heading"><p class="eyebrow">Cómo funciona</p><h1>Perfumes de lujo,<br>en la medida justa.</h1></div>
        <div class="how-steps">
            <section><p class="eyebrow">01 · Decant</p><h2>¿Qué es un decant?</h2><p>Una porción del perfume original, pasada de su frasco a un atomizador más chico. Mismo aroma, menor precio.</p></section>
            <section><p class="eyebrow">02 · Tamaños</p><h2>5 o 10 ml</h2><p>5 ml para conocerlo; 10 ml para usarlo por más tiempo.</p></section>
            <section><p class="eyebrow">03 · Pedido</p><h2>Lo pedís por WhatsApp</h2><p>Armá tu carrito, completá tus datos y confirmamos el envío por chat.</p></section>
        </div>
        <section class="how-faq" aria-labelledby="preguntas-titulo"><h2 id="preguntas-titulo" class="sr-only">Preguntas frecuentes</h2>
            @foreach (['¿Los perfumes son originales?' => 'Sí. Los decants se preparan a partir del perfume original y se presentan en atomizadores de 5 o 10 ml.', '¿Cuánto tarda el envío?' => 'Coordinamos el plazo y la entrega por WhatsApp al confirmar tu pedido.', '¿Cómo pago?' => 'El pago se coordina por WhatsApp. Al completar tus datos se guarda el pedido pendiente y se abre el chat para confirmarlo.', '¿Puedo retirar en persona?' => 'Sí. Elegí Retiro al completar tus datos. Es gratis y coordinamos el punto de retiro por WhatsApp.'] as $pregunta => $respuesta)
                <details><summary>{{ $pregunta }}<x-phosphor-plus-light aria-hidden="true" /></summary><p>{{ $respuesta }}</p></details>
            @endforeach
        </section>
        <a class="button" href="{{ route('tienda.catalogo') }}">Elegí tu perfume</a>
    </div>
</x-tienda.layout>
