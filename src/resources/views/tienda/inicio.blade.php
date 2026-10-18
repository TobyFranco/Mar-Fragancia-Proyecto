<x-tienda.layout>
    <div class="hero page-width">
        <div>
            <p class="eyebrow">Decants de perfumes originales</p>
            <h1>Perfumes de lujo,<br>en decant.</h1>
            <p class="hero-copy">Las mejores casas en 5 o 10 ml, del frasco original. Probá más fragancias, pagando solo lo que vas a usar.</p>
            <div class="hero-actions"><a class="button" href="{{ url('/perfumes') }}">Ver perfumes</a><a class="button button-outline" href="{{ url('/como-funciona') }}">Cómo funciona</a></div>
        </div>
        <div class="hero-art" role="img" aria-label="Decants de 5 y 10 ml"><div class="vial vial-small"><span>5 ml</span></div><div class="vial vial-large"><span>10 ml</span></div></div>
    </div>
    <ol class="steps">
        <li><span class="eyebrow">01</span><h2>Elegí tu perfume</h2><p>Por género, marca o familia de aroma.</p></li>
        <li><span class="eyebrow">02</span><h2>Elegí 5 o 10 ml</h2><p>5 o 10 ml, del mismo frasco original.</p></li>
        <li><span class="eyebrow">03</span><h2>Pedí por WhatsApp</h2><p>Completás tus datos y confirmamos el envío.</p></li>
    </ol>
    <section class="page-width featured" aria-labelledby="mas-probados">
        <div class="section-heading"><h2 id="mas-probados">Los más probados</h2><a class="text-link" href="{{ url('/perfumes') }}">Ver todos <x-phosphor-arrow-right-light aria-hidden="true" /></a></div>
        <div class="product-grid">@foreach ($productos as $producto)<x-tienda.tarjeta-perfume :producto="$producto" />@endforeach</div>
    </section>
</x-tienda.layout>
