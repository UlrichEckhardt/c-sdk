# syntax=docker/dockerfile:1.0.0-experimental
FROM debian:buster-slim as base

ARG TARGETPLATFORM
RUN echo "TARGETPLATFORM $TARGETPLATFORM"

# setup APT operation
# Setting frontend to noninteractive avoids a bunch of warnings like
# "debconf: unable to initialize frontend: Dialog"
ENV DEBIAN_FRONTEND=noninteractive
# never ask for confirmation
RUN echo "APT::Get::Assume-Yes true;" >> /etc/apt/apt.conf.d/custom.conf
# always be quiet
RUN echo "quiet true;" >> /etc/apt/apt.conf.d/custom.conf
# never install recommendations
RUN echo "APT::Install-Recommends false;" >> /etc/apt/apt.conf.d/custom.conf

# add for curl testing
RUN echo "deb http://http.us.debian.org/debian/ testing main non-free contrib" > /etc/apt/sources.list.d/curl-fix.list

# install requirements
#  - apt-transport-https for HTTPS package repositories
#  - ca-certificates for HTTPS communication
#  - curl to download some keys
#  - gnupg2 is required to add repository keys for APT
RUN apt-get update \
    && apt-get upgrade \
    && apt-get install \
        apt-transport-https \
        ca-certificates \
        curl \
        gnupg2

# add repository for Ondřej Surý's PHP packages
RUN curl -s https://packages.sury.org/php/apt.gpg > /etc/apt/trusted.gpg.d/php.gpg
RUN echo "deb https://packages.sury.org/php/ buster main" > /etc/apt/sources.list.d/php.list

# add repository for NewRelic extension
RUN echo 'deb https://apt.newrelic.com/debian/ newrelic non-free' | tee /etc/apt/sources.list.d/newrelic.list
RUN curl -fksSL https://download.newrelic.com/548C16BF.gpg | apt-key add -


RUN apt-get update \
    && apt-get install \
        php7.4-cli \
        php7.4-curl \
        newrelic-daemon

RUN apt-get install htop nano less
WORKDIR /var/app
