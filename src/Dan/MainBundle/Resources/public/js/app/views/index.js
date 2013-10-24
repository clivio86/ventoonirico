define([
  'jquery-loader', 
  'underscore', 
  'backbone-loader',
  'masonry/masonry',
  'app/collections/users',
  'app/collections/games',
  'app/collections/desired-games',
  'app/util/current-user',

], function($, _, Backbone, Masonry, UserCollection, GameCollection, DesiredGameCollection, currentUser){
    $.ventoonirico = {};

    $.ventoonirico.GameCountView = Backbone.View.extend({
        initialize: function() {
            this.listenTo(this.model, 'sync', this.render);
        },
        template: _.template($('#game-count').html()),
        render: function() {
            this.$el.html(this.template({count: this.model.length}));
            return this;
        },
    });

    $.ventoonirico.GameListView = Backbone.View.extend({
        initialize: function() {
            this.listenTo(this.model, 'sync', this.render);
        },
        template: _.template($('#game-list').html()),
        render: function() {
            this.$el.parents().find(".loading").hide();
            this.$el.html(this.template(this.model));
            this.model.forEach(this.renderGame);
            return this;
        },
        renderGame: function(game) {
            var gameView = new $.ventoonirico.GameView({
                model: game
            });
            this.$('table.game-list').append(gameView.render().el);
        }
    });
    
    $.ventoonirico.GameView = Backbone.View.extend({
        tagName: 'tr',
        initialize: function() {
            this.listenTo(this.model, 'change', this.render);
        },
        template: _.template($('#game').html()),
        render: function() {
            this.setElement(this.template({game: this.model.toJSON()}));
            var gameStatusView = new $.ventoonirico.GameStatusView({
                el: this.$el.find(".game-status"),
                model: {
                    game: this.model,
                    user: currentUser
                }
            });
            return this;
        }
    });
    
    $.ventoonirico.GameStatusView = Backbone.View.extend({
        initialize: function() {
            this.listenTo(this.model.game, 'change', this.render);
            this.listenTo(this.model.user, 'change', this.render);
            this.render()
        },
        events: {
            "click .desire-create": "createDesire",
            "click .desire-remove": "removeDesire",
        },
        render: function() {
            var desire = this.model.game.get('desire');
            if (!desire) {
                if (this.model.user.isLogged()) {
                    this.template = _.template($('#game-status-user-nodesire').html()),
                    this.$el.html(this.template(this.model));
                    return this;
                } else {
                    this.template = _.template($('#game-status-nouser-nodesire').html()),
                    this.$el.html(this.template({}));
                    return this;
                }
            }
            
            var desireView = new $.ventoonirico.DesireView({
                model: {
                    user: this.model.user,
                    game: this.model.game,
                    desire: desire
                }                    
            });
            this.$el.html(desireView.el);
        },
        createDesire: function() {
            this.model.game.createDesire(this.model.user);
            return false;
        },        
        removeDesire: function() {
            this.model.game.removeDesire();
            return false;
        }
    });
    
    $.ventoonirico.DesireView = Backbone.View.extend({
        initialize: function() {
//            this.listenTo(this.model.desire, 'change', this.render);
            this.listenTo(this.model.desire, 'add:joins', this.render);
            this.listenTo(this.model.desire, 'remove:joins', this.render);
            this.setElement($('#game-status-desire').html());
            this.render();
        },
        events: {
            "click .join-add": "addJoin",
            "click .join-remove": "removeJoin"
        },
        render: function() {
            var user = this.model.user;
            var game = this.model.game;
            var desire = this.model.game.get('desire');
            
            this.$el.html(_.template($('#game-status-desire-player_main').html(), {user: user, owner: desire.get('owner')}));

            var joins = desire.get('joins');
            var users = new UserCollection([desire.get('owner')]);
            
            for(var i=0; i<game.get('maxPlayers')-1; i++) {
                var join  = joins.at(i);
                if ( join) {
                    var guest = users.get(join.get('user')) != undefined;
                    users.push(join.get('user'));
                    this.$el.append(_.template($('#game-status-desire-player_joined').html(), {user: user, join: join, guest:guest}));
                } else {
                    this.$el.append(_.template($('#game-status-desire-player_nobody').html(), {user: user}));
                }
            }
    
            return this;
        },
        addJoin: function() {
            this.model.desire.addJoin(this.model.user);
            return false;
        },
        removeJoin: function() {
            this.model.desire.removeJoin(this.model.user);
            return false;
        }
    });
    
    $.ventoonirico.DesiredGameListView = Backbone.View.extend({
        initialize: function() {
            this.listenTo(this.model, 'sync', this.render);
        },
        template: _.template($('#desired-game-list').html()),
        render: function() {
            this.$el.html(this.template(this.model));
            this.model.forEach(this.renderGame);
            return this;
        },
        renderGame: function(game, index, games) {
            var desiredGameView = new $.ventoonirico.DesiredGameView({
                model: game
            });
            var el = desiredGameView.render().el;
            this.$('div.desired-game-list').append(el);
            
            if (index==games.length-1) {
                new Masonry(this.$('.masonry').get(0),{
                    itemSelector: '.item',
                    "gutter": 10
                });
            }
        }
    });
    
    $.ventoonirico.DesiredGameView = Backbone.View.extend({
        tagName: 'div',
        initialize: function() {
            this.listenTo(this.model, 'change', this.render);
        },
        template: _.template($('#desired-game').html()),
        render: function() {
            this.setElement(this.template({game: this.model.toJSON()}));
            var gameStatusView = new $.ventoonirico.GameStatusView({
                el: this.$el.find(".game-status"),
                model: {
                    game: this.model,
                    user: currentUser
                } 
           });
            return this;
        }
    });

    
    $.ventoonirico.IndexView = Backbone.View.extend({
        el: $("#app"),
        template: _.template($('#games').html()),
        events: {
        },
        initialize: function() {
            this.render();
        },
        render: function() {
            this.$el.html(this.template({}));
            var gameCollection = new GameCollection();
            var desiredGameCollection = new DesiredGameCollection();

            var gameCountView = new $.ventoonirico.GameCountView({'model': gameCollection});
            var desiredGameListView = new $.ventoonirico.DesiredGameListView({'model': desiredGameCollection});
            var gameListView = new $.ventoonirico.GameListView({'model': gameCollection});

            this.$("#game-list").append(gameListView.el);
            this.$("#desired-game-list").append(desiredGameListView.el);
            this.$("#game-count").append(gameCountView.el);
            
            desiredGameCollection.fetch();
            currentUser.fetch();
            gameCollection.fetch();
        },
    });

    return $.ventoonirico.IndexView;

});
