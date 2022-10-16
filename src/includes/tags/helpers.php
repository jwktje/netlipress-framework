<?php
function parse_markdown($string) {
    return (new Parsedown())->parse($string);
}
