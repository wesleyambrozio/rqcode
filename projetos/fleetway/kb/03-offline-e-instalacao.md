# BIELFRA — instalação PWA e operação offline

Público: customer, support
Tipo: troubleshooting
Tags: pwa, offline, instalação, sincronização, celular

O BIELFRA possui manifesto, service worker, página offline e fila de alterações. Antes do primeiro uso offline, abrir o domínio oficial com internet e concluir a instalação/atualização.

Quando a aplicação informar que a alteração foi salva no aparelho, não repetir o cadastro. Reconectar e manter o aplicativo aberto para sincronização. Antes de limpar cache, coletar evidência e confirmar se há itens pendentes, pois a limpeza pode remover dados locais ainda não enviados.

A PWA v8 ainda exige validação física final no HTTPS candidato: instalação, modo standalone, atualização, sessão, modo avião e reconexão. O agente não deve afirmar que essa homologação terminou até a evidência ser registrada.

Fonte: `fleetway/public/sw.js`, manifesto e `fleetway/docs/GATE6_RELEASE_CHECKLIST.md`.
