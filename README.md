# clean-arch-leilao

Prática do vídeo sobre clean arch

[https://www.youtube.com/watch?v=cCc37q3CXuc](https://www.youtube.com/watch?v=cCc37q3CXuc)

## Sistema de leilão

* O leilão abre e fecha em horários específicos, não serão aceitos lances fora destes horários
* Existe um valor mínimo, lances abaixo do valor mínimo não serão aceitos
* Um novo lance deve sersempre maior que o maior lance existente, mais o valor mínimo de acréscimo
* Não é permitido que a mesma pessoa dê lances seguidos
* O leilão só pode ser encerrado quando passar do horário de término

* Deve existir uma API para cadastrar leilões e dar lances
* Deve existir um WebSocket para acompanhar os lances
