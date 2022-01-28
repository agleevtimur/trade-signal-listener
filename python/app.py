from logging import PercentStyle
from telethon import events
from telethon.sync import TelegramClient
import os, requests
from dotenv import load_dotenv


load_dotenv()

API_ID = os.getenv('TELEGRAM_API_ID')
API_HASH = os.getenv('TELEGRAM_API_HASH')
R2BC_CHANNEL_ID = os.getenv('R2BC_CHANNEL_ID')
USER_ID = os.getenv('TIMUR_USER_ID')
URL = os.getenv('SIGNAL_RECEIVER_URL')
print(URL)

client = TelegramClient('session', API_ID, API_HASH, sequential_updates=True).start()

@client.on(events.NewMessage)
async def event_handler(event):
    print('new update')
    if (event.chat is None):
        peer = str(event.sender_id)
    else:
        peer = str(event.chat.id)

    if (peer == R2BC_CHANNEL_ID or peer == USER_ID):
        if (peer == R2BC_CHANNEL_ID):
            messageLink = f'https://t.me/r2bcfx/{event.id}'
        else:
            messageLink = 'test'
        try:
            response = requests.post(url = URL, json = {"peer": "r2bc", "message": event.raw_text, "messageLink": messageLink})
            if (response.status_code != 200):
                print('bad request', response.reason)
        except Exception as e:
            print('failed send request')

client.start()
client.run_until_disconnected()
