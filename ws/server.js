import { WebSocketServer } from "ws";

const PORT = 8081;
const wss = new WebSocketServer({ port: PORT });

wss.on("connection", (socket) => {
  console.log("connected");
  socket.send(
    JSON.stringify({type: "connected",message: "server is working",})
  );
//   socket.on("message", (message) => {
//     console.log("msg from client",message.toString());
//     socket.send(
//       JSON.stringify({
//                 type: "echo",
//         message: message.toString()}));
//   });

    socket.on("close", () => {
      console.log("client disconnected");
    });

});
console.log(`port ${PORT}`);