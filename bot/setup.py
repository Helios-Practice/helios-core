from pathlib import Path

items = {
    "TOKEN": "Enter your bot's token: ",
    "RCON_ADDR": "Helios' direct IP: ",
    "RCON_PASS": "Helios' RCON password: ",
    "RCON_PORT": "Helios' RCON port [19132]: ",
    "ADMIN_ROLES": "Discord staff role: ",
    "WHITELIST_LIMIT": "Amount to whitelist: "
}

for key in items:
    items[key] = input(items[key])

data = f"""
TOKEN = "{items['TOKEN']}"
RCON_ADDR = "{items['RCON_ADDR']}"
RCON_PASS = "{items['RCON_PASS']}"
RCON_PORT = {items['RCON_PORT']}
ADMIN_ROLES = ["{items['ADMIN_ROLES']}"]
BYPASS_ROLES = []
WHITELIST_LIMIT = {items['WHITELIST_LIMIT']}
"""

path = Path("config/")
path.mkdir(parents=True)
path = Path("config/config.py")
with path.open("w") as f:
    f.write(data)