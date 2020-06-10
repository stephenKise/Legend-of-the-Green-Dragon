var app = angular.module('LOTGD', []);

app.factory(
    'colors', function () {
        var colors = {};
        colors.names = {
            '1': 'colDkBlue',
            '!': 'colLtBlue',
            '2': 'colDkGreen',
            '@': 'colLtGreen',
            '3': 'colDkCyan',
            '#': 'colLtCyan',
            '4': 'colDkRed',
            '$': 'colLtRed',
            '5': 'colDkMagenta',
            '%': 'colLtMagenta',
            '6': 'colDkYellow',
            '^': 'colLtYellow',
            '7': 'colDkWhite',
            '&': 'colLtWhite',
            'q': 'colDkOrange',
            'Q': 'colLtOrange',
            '~': 'colBlack',
            ')': 'colLtBlack',
            'r': 'colRose',// Perhaps    two   have secondary
            'R': 'colRose',//       these   can    a         color
            'v': 'coliceviolet',
            'V': 'colBlueViolet',
            'g': 'colXLtGreen', // Perhaps    two   have secondary
            'G': 'colXLtGreen', //       these   can    a         color
            't': 'colLtBrown',
            'T': 'colDkBrown',
            'j': 'colMdGrey',
            'J': 'colMdBlue',
            'e': 'colDkRust',
            'E': 'colLtBlack',
            'l': 'colDkLinkBlue',
            'L': 'colLtLinkBlue',
            'x': 'colburlywood',
            'X': 'colbeige',
            'u': 'colkhaki',
            'Y': 'coldarkkhaki',
            'k': 'colaquamarine',
            'K': 'coldarkseagreen',
            'p': 'collightsalmon',
            'P': 'colsalmon',
            'm': 'colwheat',
            'M': 'coltan'
        };
        colors.format = (text) => {
            var out = '';
            var x = 0;
            for (; x < text.length; x++) {
                y = text.substr(x, 1);
                if (y == '<') {
                    out += '&lt;';
                    continue;
                }
                else if (y == '>') {
                    out += '&gt;';
                    continue;
                }
                else if (y == '\n') {
                    out += '<br />';
                    continue;
                }
                else if (y == '`') {
                    if (x < text.length-1) {
                        z = text.substr(x+1, 1);
                        switch (z) {
                        case '0':
                            out += '</span>';
                            break;
                        default:
                            if (colors.names[z] == undefined) {
                                out += "</span>";
                            }
                            else {
                                out += "</span><span class='" + colors.names[z] + "'>";
                            }
                            break;
                        }
                        x++;
                    }
                }
                else {
                    out += y;
                }
            }
            //return $sce.trustAsHtml(out);
            return out;
        };
        return colors;
    }
);

app.controller(
    'commentary', function (colors, $scope, $http, $sce, $interval) {
        $scope.username = '';
        $scope.acctid = 0;
        $scope.insertComment = '';
        $scope.insertCommentFormatted = '';
        $scope.commentsArray = [];
        $scope.recentComment = [];
        $scope.doEdit = false;
        $scope.timeToStop = 120;
        $scope.timeRun = 0;
        $scope.alert = false;
        $scope.alertMessage = '';
        var time, tempTime = new Date;
        $scope.init = (name, acctid, URI) => {
            $scope.username = $scope.formatColors(name, false);
            $scope.acctid = acctid;
            $scope.URI = URI;
            $scope.fetchCommentary();
            $interval($scope.fetchCommentary, 100000);
        }
        $scope.fetchCommentary = (force = false) => {
            if ($scope.timeRun <= $scope.timeToStop || force === true) {
                $scope.timeRun++;
                //var response = [];
                $http.get('runmodule.php?module=api&mod=ngchat&act=fetchCommentary')
                .then(
                    (data, headers) => {
                    $scope.formatCommentary(data);
                    $scope.commentsArray = data;
                    data.data.forEach(
                            function (arr) {
                                if (arr.author == $scope.acctid && arr.deleted != 1) {
                                    $scope.recentComment = {commentid: arr.commentid, comment: arr.comment};
                                }
                            }
                        );
                    },
                    (data, status, headers) => {
                    console.log(status);
                    }
                );
                if (force === true) {
                    $scope.wipeTimer();
                }
            }
            else {
                $scope.killCommentary();
            }
        }
        $scope.sendCommentary = () => {
            var payload = {commentid: 0, comment: $scope.insertComment};
            time = new Date;
            x = Math.floor((time - tempTime)/1000);
            if (x < 5) {

                $scope.alertMessage = 'Please wait ' + (5 - x) + ' seconds before posting!';
                $scope.alert = true;
                return false;
            }
            tempTime = new Date;
            if ($scope.insertComment == '') {
                return false;
            }
            if ($scope.doEdit === true) {
                payload = {
                    commentid: +$scope.recentComment['commentid'],
                    comment: $scope.insertComment,
                };
            }
            $http.post('runmodule.php?module=api&mod=ngchat&act=sendCommentary', payload)
            .then(
                () => {
                $scope.fetchCommentary();
                $scope.wipeTimer();
                },
                () => {
                console.log('fail');
                }
            );
        console.log(payload);
        $scope.clearInput();
        $scope.fetchCommentary();
        $scope.wipeTimer();
        }
        $scope.removeComment = (id) => {
            console.log('Removing comment id #' + id);
            $http.post('runmodule.php?module=api&mod=ngchat&act=removeComment', id)
            .then(
                () => {
                $scope.fetchCommentary();
                $scope.wipeTimer();
                },
                () => {
                console.log('fail');
                }
            );
        }
        $scope.killCommentary = () => {
            $scope.comments = [
            {
                'commentid': '0',
                'section': 'null',
                'author': '0',
                'comment': ':`^`bPlease continue typing or hit refresh to reload the chat!`b',
                'deleted': '0',
                'postdate': '0000-00-00 00:00:00',
                'name': ''
            }
            ];
        }
        $scope.handleInput = () => {
            $scope.insertCommentFormatted = $scope.formatColors($scope.insertComment, true);
            if ($scope.timeRun >= $scope.timeToStop) {
                $scope.fetchCommentary(true);
            }
            $scope.wipeTimer();
            if ($scope.insertComment == '') {
                $scope.clearInput();
            }
            else if ($scope.insertComment == '/edit') {
                $scope.insertComment = $scope.recentComment['comment'];
                $scope.doEdit = true;
            }
            else if ($scope.insertComment == '/nvm' 
                || $scope.insertComment == '/rmv'
            ) {
            $scope.removeComment($scope.recentComment['commentid']);
            $scope.clearInput();
            }
        
        }
        $scope.clearInput = () => {
            $scope.insertComment = '';
            $scope.insertCommentFormatted = '';
            $scope.doEdit = false;
        }
        $scope.wipeTimer = () => {
            $scope.alert = false;
            $scope.timeRun = 0;
        }
        $scope.formatColors = (text, commentary, user = $scope.username, acctid = 0, deleted = 0) => {
            var out = user;
            if (acctid != 0) {
                out = "<a href='bio.php?char=" + acctid + "&ret=" + $scope.URI + "'>" + out + "</a>";
            }
            if (deleted == 1) {
                out = "<div style='opacity: .3;'>" + out;
            }
            var end = '</span>';
            var x = 0;
            var y = '';
            var z = '';
            if (commentary) {
                if (text.substr(0, 2) == '::') {
                    x = 2;
                }
                else if (text.substr(0, 1) == ':') {
                    x = 1;
                }
                else if (text.substr(0, 3) == '/me') {
                    x = 3;
                }
                else if (text.substr(0, 5) == '/ooc ' || text.substr(0, 5) == ':ooc ') {
                    x = 5;
                }
                if (text.substr(0, 5) == '/ooc ' || text.substr(0, 5) == ':ooc ') {
                    out = '<span class=\'colLtOrange\'>(OOC)</span> ' + out;
                }
                if (text.substr(0, 2) == '::' || text.substr(0, 1) == ':' || text.substr(0, 3) == '/me' || text.substr(0, 4) == ':ooc') {
                    out += '</span> <span class=\'colLtWhite\'>';
                }
                else {
                    out += '</span> <span class=\'colDkCyan\'>says, "</span><span class=\'colLtCyan\'>';
                    end += '</span><span class=\'colDkCyan\'>"';
                }
            }
            return $sce.trustAsHtml(out + colors.format(text) + end);
        }
        $scope.formatCommentary = (data) => {
            var response = data.data;
            $scope.comments = [];
            for (var i = 0; i < response.length; i++) {
                temp = $scope.formatColors("this is a comment", true);
                $scope.comments[i] = response[i];
            }
            return $scope.comments;
        }
    }
)
.directive(
    'ngEnter', () => {
    return (scope, element, attrs) => {
    element.bind(
                'keydown keypress', (event) => {
                if (event.which === 13) {
                    scope.$apply(
                    () => {
                        scope.$eval(attrs.ngEnter);
                        }
                    );
                    event.preventDefault();
                }
                }
        );
    };
    }
);

app.controller(
    'list', function ($scope, $http, $sce) {
        var type;
        var resource;
        $scope.init = () => {
            $http.get('runmodule.php?module=api&mod=list&act=listOnline')
            .then(
                function (data, status) {
                    $scope.users = data.data;
                    console.log(data);
                },
                function (data, status) {
                    console.log(data);
                }
            )
        };
    }
);


window.onload = function () {
    /*document.getElementById('DB_USEPREFIX').addEventListener('change', function() {
        var style = this.value == 1 ? 'block' : 'none';
        var type = this.value == 1 ? 'text' : 'hidden';
        document.getElementById('DB_PREFIX_SELECTED').style.display = style;
        document.getElementById('DB_PREFIX').type = type;
    });*/
    var datacacheSelector = document.getElementById('DB_USEDATACACHE');
    if (datacacheSelector) {
        if (datacacheSelector.value == 1) {
            document.getElementById('DB_DATACACHE').style.display = 'inline';
        }
        datacacheSelector.addEventListener(
            'change', function () {
                console.log(this);
                var style = this.value == 1 ? 'inline' : 'none';
                document.getElementById('DB_DATACACHE').style.display = style;
            }
        );
    }
}