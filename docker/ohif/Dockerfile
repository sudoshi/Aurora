FROM node:18-alpine AS builder

RUN apk add --no-cache git

WORKDIR /ohif
RUN git clone --depth 1 --branch v3.9.2 https://github.com/OHIF/Viewers.git .

RUN yarn install --frozen-lockfile

ENV PUBLIC_URL=/ohif/
RUN yarn run build

# Final stage: static files + Aurora customizations
FROM alpine:3.20
COPY --from=builder /ohif/platform/app/dist /dist

# Inject Aurora bridge script
COPY ohif-bridge.js /dist/ohif-bridge.js

# Inject into index.html: bridge script before </body>
RUN sed -i 's|</body>|<script src="/ohif/ohif-bridge.js"></script></body>|' /dist/index.html
