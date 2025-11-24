<div class='clan_create form'>
    <form action='clan.php?op=new&apply=1' method='POST'>
        `b`c`@New Clan Creation Form`0`c`b
        <label for='clan_name'>Clan Name:</label>
        <input name='clan_name' maxlength='50' value='%s' /><br />
        <label for='clan_tag'>Clan Tag:</label>
        <input name='clan_tag' maxlength='50' size='5' value='%s'/>
        <div class='note'>
            Note, color codes are prohibited in Clan names and tags.
            The Clan name is shown throughout areas of the game world.
            The Clan tag is displayed alongside members' names in several of the game.
        </div>
        <input type='submit' class='button' value='Apply'>
    </form>
</div>

<style>
    .clan_create > .form {
        margin-left: 10px;
        margin-right: 10px;
    }
</style>