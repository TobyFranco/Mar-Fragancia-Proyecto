import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('busquedaEnVivo', () => ({
    cargando: false, estado: '', temporizador: null, peticion: null, version: 0,
    cancelar() {
        clearTimeout(this.temporizador);
        this.peticion?.abort();
        this.version++;
        this.cargando = false;
    },
    buscar(consulta) {
        this.cancelar();
        this.estado = '';
        const version = this.version;
        this.temporizador = setTimeout(async () => {
            this.peticion = new AbortController();
            this.cargando = true;
            const url = new URL(this.$refs.formulario.action);
            url.searchParams.set('q', consulta);
            url.searchParams.set('sugerencias', '1');
            try {
                const respuesta = await fetch(url, { signal: this.peticion.signal, headers: { Accept: 'text/html' } });
                if (!respuesta.ok) throw new Error('No se pudo buscar');
                const html = await respuesta.text();
                if (version === this.version) {
                    this.$refs.resultados.innerHTML = html;
                    this.estado = 'Sugerencias actualizadas.';
                }
            } catch (error) {
                if (error.name !== 'AbortError' && version === this.version) this.estado = 'Presioná Enter para ver los resultados.';
            } finally {
                if (version === this.version) this.cargando = false;
            }
        }, 200);
    },
    destroy() { this.cancelar() },
}));

Alpine.start();
