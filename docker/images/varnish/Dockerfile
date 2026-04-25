FROM varnish:7.2-alpine

# install build dependencies
USER root
RUN set -e; \
    apk add --no-cache $VMOD_DEPS; \
    \
# install one, possibly multiple vmods
    install-vmod https://github.com/varnish/varnish-modules/releases/download/0.21.0/varnish-modules-0.21.0.tar.gz; \
    \
# clean up
    apk del --no-network $VMOD_DEPS
USER varnish
