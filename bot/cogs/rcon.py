# thank fuck for the mcrcon module...

from discord import colour
from discord.ext import commands
import discord
from mcrcon import MCRcon

from config.config import RCON_PASS, RCON_ADDR, RCON_PORT, BYPASS_ROLES, ADMIN_ROLES
from helpers.account_manager import AccountManager


class RCON(commands.Cog):

    def __init__(self, bot):
        self.bot = bot
        self.rcon = MCRcon(RCON_ADDR, RCON_PASS, RCON_PORT)
        self.rcon.connect()
        self.am = AccountManager()

    @commands.command(name="rcon")
    @commands.has_any_role(*ADMIN_ROLES)
    async def rcon_send(self, ctx, *command):
        command = " ".join(command)
        resp = self.rcon.command(command)

        embed = discord.Embed(title="RCON", description=f"Command: {command}", colour=0x0FFF0F)
        embed.set_author(name=ctx.author.name, icon_url=ctx.author.avatar_url)
        data = resp if resp else "Command succeeded."
        embed.add_field(name="Result", value=data)

        await ctx.channel.send(embed=embed)

    @commands.command(name="whitelist")
    async def rcon_whitelist(self, ctx, username: str):
        roles = [role.name for role in ctx.author.roles]
        force = any([role in BYPASS_ROLES for role in roles])
        result, data = self.am.whitelist_add(username, ctx.author.id, override=force)
        resp = None

        if result:
            resp = self.rcon.command(f"whitelist add {username}")
        else:
            resp = data

        embed = discord.Embed(title="Whitelist", description=f"User: {username} ({ctx.author.id})", colour=0x0F0FFF)
        embed.set_author(name=ctx.author.name, icon_url=ctx.author.avatar_url)
        embed.add_field(name="Result", value=resp)

        await ctx.channel.send(embed=embed)

    @commands.command(name="unwhitelist")
    @commands.has_any_role(*ADMIN_ROLES)
    async def rcon_unwhitelist(self, ctx, username: str):
        result, data = self.am.whitelist_remove(username)
        resp = None

        if result:
            resp = self.rcon.command(f"whitelist remove {username}")
        else:
            resp = data

        embed = discord.Embed(title="Unwhitelist", description=f"User: {username}", colour=0xFF0F0F)
        embed.set_author(name=ctx.author.name, icon_url=ctx.author.avatar_url)
        embed.add_field(name="Result", value=resp)

        await ctx.channel.send(embed=embed)

    @commands.command(name="purgeuser")
    @commands.has_any_role(*ADMIN_ROLES)
    async def rcon_purge(self, ctx, username):
        result, data = self.am.whitelist_remove(username)

        resp = self.rcon.command(f"whitelist remove {username}")

        embed = discord.Embed(title="Purge", description=f"User: {username}", colour=0xFF0F0F)
        embed.set_author(name=ctx.author.name, icon_url=ctx.author.avatar_url)
        embed.add_field(name="WLDB Result", value=data)
        embed.add_field(name="RCON Result", value=resp)

        await ctx.channel.send(embed=embed)

def setup(bot):
    bot.add_cog(RCON(bot))
