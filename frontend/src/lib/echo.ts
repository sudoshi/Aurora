// Laravel Echo stub — will be configured when WebSockets are set up
let echoInstance: any = null;

export function getEcho() {
  return echoInstance;
}

export function setEcho(instance: any) {
  echoInstance = instance;
}

export default echoInstance;
