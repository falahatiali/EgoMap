FROM node:20-alpine

WORKDIR /app

RUN apk add --no-cache python3 make g++

# Dependencies are installed at runtime via docker compose --profile build
# so package-lock.json stays in sync with the mounted project files.

CMD ["npm", "run", "build"]
