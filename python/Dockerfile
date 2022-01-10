FROM python:3.11.0a3-slim

WORKDIR /app

RUN pip3 freeze > requirements.txt
RUN pip3 install -r requirements.txt
RUN python3 -m pip install --upgrade pip
RUN python3 -m pip install --upgrade telethon
RUN pip3 install python-dotenv
RUN pip3 install requests

COPY . .

CMD [ "python3", "./app.py"]
