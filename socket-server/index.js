import express from 'express'
import http from 'http'
import socketIO from 'socket.io'

const port = process.env.SOCKET_PORT || 8001

const app = express()
const server = http.createServer(app)
const io = socketIO(server)

io
  .of('/responses')
  .on('connection', (socket) => {
    console.log('User connected with id %s', socket.id)

    const identifier = socket.handshake.query.identifier
    const USERID = socket.handshake.query.USERID
    let fetchResponses

    if (identifier) {
      socket.join(identifier)
    }

    if (identifier && USERID) {
      socket.join(identifier + '/' + USERID)
    }

    socket.on('new-responses', (responses, callback) => {
      console.log('New responses for %s', identifier)

      io.of('/responses').to(identifier + '/' + USERID).emit('own-responses-updated', responses)
      io.of('/responses').to(identifier).emit('responses-updated', responses)

      callback(true)
    })

    socket.on('disconnect', () => {
      console.log('User with id %s disconnected', socket.id)

      clearInterval(fetchResponses)
    })
  })

server.listen(port, () => console.log(`Websocket listening on port ${port}`))
