<div id='superuser_renaming_form' style='display: none;'>
    <h3>Superuser Renaming:</h3>
    <form action='clan.php?detail=%s' method='POST'>
        <label for='clan_name'>Long Name:</label>
        <input name='clan_name' value='%s' maxlength=50 /><br />
        <label for='clan_tag'>Short Name:</label>
        <input name='clan_tag' value='%s' maxlength=5 /><br />
        <input type='submit' class='button superuser' value='Save' />
        <input type='submit' name='toggle_block' class='button superuser'
            value='Block/Unblock description' />
    </form>
</div>
<script language='JavaScript'>
    var form = document.getElementById('superuser_renaming_form')
    toggleForm = () => {
        console.log('flip')
        form.style.display = form.style.display === 'none' ? 'block' : 'none'
    }
</script>
<a id='toggle_form_display' onclick='toggleForm()'>%s</a>
<br />