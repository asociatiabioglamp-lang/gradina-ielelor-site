import path from "path"
import react from "@vitejs/plugin-react"
import { defineConfig, type Plugin } from "vite"
import { inspectAttr } from 'kimi-plugin-inspect-react'
import { spawn } from "child_process"

// Starts the local API server (Express + IMAP) together with the dev server
function startApiServer(): Plugin {
  let api: ReturnType<typeof spawn> | null = null
  return {
    name: "gradina-api-server",
    configureServer() {
      api = spawn(process.execPath, [path.resolve(__dirname, "server/index.js")], { stdio: "inherit" })
      process.on("exit", () => api?.kill())
    },
    closeBundle() {
      api?.kill()
    },
  }
}

// https://vite.dev/config/
export default defineConfig({
  base: './',
  plugins: [inspectAttr(), react(), startApiServer()],
  server: {
    port: 7100,
    proxy: {
      "/api": "http://localhost:8787",
    },
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
})
