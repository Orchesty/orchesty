import { setupWorker } from "msw"
import { handlers } from "./handlers"

export async function startWorker() {
  const worker = setupWorker(...handlers)
  await worker.start({
    onUnhandledRequest(req, print) {
      if (req.url.pathname.startsWith("/assets/")) {
        return
      }
      if (req.url.pathname.startsWith("/whitelabel/")) {
        return
      }
      print.warning()
    },
  })
}
