import amqp from "amqplib";
import { WebSocketServer } from "ws";

const WS_PORT = 8081;

// Rabbitmq config
const RABBIT_URL = "amqp://guest:guest@rabbitmq:5672";
const EXCHANGE_NAME = "match.events";
const EXCHANGE_TYPE = "direct";
const QUEUE_NAME = "match.events.ws";
const ROUTING_KEY = "match.event";

const wss = new WebSocketServer({ port: WS_PORT });

wss.on("connection", (socket) => {
    console.log("client connected");
    socket.send(
        JSON.stringify({
            type: "connected",
            message: "ws server connected"
        })
    );

    socket.on("close", () => {
        console.log("client disconnected");
    })
});

function broadcast(payload) {
    const data = JSON.stringify(payload);
    wss.clients.forEach((client) => {
        if (client.readyState === 1) {
            client.send(data);
        }
    })
}

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms))
}

async function consumeMq() {
    while (true) {
        try {
            console.log("connectin to rabbit");
            const connection = await amqp.connect(RABBIT_URL);
            const channel = await connection.createChannel();

            await channel.assertExchange(EXCHANGE_NAME, EXCHANGE_TYPE, {
                durable: true,
            });
            await channel.assertQueue(QUEUE_NAME, { durable: true });

            await channel.bindQueue(QUEUE_NAME, EXCHANGE_NAME, ROUTING_KEY);
            await channel.prefetch(1);

            await channel.consume(
                QUEUE_NAME,
                (msg) => {
                    if (!msg) {
                        return;
                    }
                    try {
                        const raw = msg.content.toString("utf-8");
                        const parsed = JSON.parse(raw);

                        const isValid =
                            parsed &&
                            (parsed.eventType === "goal" || parsed.eventType === "foul") &&
                            typeof parsed.matchId === "string";

                        if (!isValid) {
                            console.error("Invalid payload:", raw);
                            channel.nack(msg, false, false);
                            return;
                        }

                        console.log("Received event:", parsed);

                        broadcast({
                            type: "match_event",
                            payload: parsed,
                        });

                        channel.ack(msg);
                    } catch (error) {
                        console.error("Consumer error:", error);
                        channel.nack(msg, false, true);
                    }
                },
                {
                    noAck: false,
                }
            );

            connection.on("error", (error) => {
                console.error("RabbitMQ connection error:", error.message);
            });

            connection.on("close", async () => {
                console.error("RabbitMQ connection closed. Reconnecting...");
                await sleep(2000);
                startRabbitConsumer();
            });

            console.log("RabbitMQ consumer ready");
            break;
        } catch (error) {
            console.error("RabbitMQ startup failed:", error.message);
            await sleep(2000);
        }
    }
}

console.log(`server listening on port ${WS_PORT}`);
consumeMq();