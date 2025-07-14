import axios, { AxiosResponse } from "axios";
import dayjs, { Dayjs } from "dayjs";
import { notification } from "ant-design-vue";

function handleResponse(r: AxiosResponse) {
    let data = JSON.parse(r.data);
    if (data.error) {
        notify('error', data.error);
        return;
    }
    if (data.message) {
        notify(data.message.type, data.message.title);
        return;
    }
}

export async function getRequest(url: string): Promise<any> {
    return new Promise((resolve, reject) => {
        axios.get(url, { withCredentials: true })
            .then(response => {
                resolve(response.data);
            })
            .catch((err) => {
                reject(err);
            });
    })
}

export async function postRequest(
    url: string,
    data: any,
    thenHandler: Function | undefined = undefined,
    errHandler: Function | undefined = undefined
): Promise<any> {
    return new Promise((resolve, reject) => {
        axios.post(url, data, { withCredentials: true })
            .then(response => {
                if (thenHandler) thenHandler(response);
                handleResponse(response);
                resolve(response.data);
            })
            .catch((err) => {
                if (errHandler) errHandler(err);
                handleResponse(err);
                reject(err);
            })
    })
}

export async function putRequest(
    url: string,
    data: any,
    thenHandler: Function | undefined = undefined,
    errHandler: Function | undefined = undefined
): Promise<any> {
    return new Promise((resolve, reject) => {
        axios.put(url, data, { withCredentials: true })
            .then(response => {
                if (thenHandler) thenHandler(response);
                handleResponse(response);
                resolve(response.data);
            })
            .catch((err) => {
                if (errHandler) errHandler(err);
                handleResponse(err);
                reject(err);
            })
    })
}

export async function deleteRequest(
    url: string,
    data: any,
    thenHandler: Function | undefined = undefined,
    errHandler: Function | undefined = undefined
): Promise<any> {
    return new Promise((resolve, reject) => {
        axios.delete(url, { withCredentials: true, data: data })
            .then(response => {
                if (thenHandler) thenHandler(response);
                handleResponse(response);
                resolve(response.data);
            })
            .catch((err) => {
                if (errHandler) errHandler(err);
                handleResponse(err);
                reject(err);
            })
    })
}

export function getTimeString(): Dayjs {
    return dayjs();
}

export function notify(type: string, message: string) {
    const n = () => {
        notification[type]({
            message: message
        });
    }
    n();
    return;
}

export function getNextElement(cursorPosition: number, currentElement: Element) {
    // Получаем объект с размерами и координатами
    const currentElementCoord = currentElement.getBoundingClientRect();
    // Находим вертикальную координату центра текущего элемента
    const currentElementCenter = currentElementCoord.y + currentElementCoord.height / 2;
    // Если курсор выше центра элемента, возвращаем текущий элемент
    // В ином случае — следующий DOM-элемент
    const nextElement = (cursorPosition < currentElementCenter) ?
        currentElement :
        currentElement.nextElementSibling;
    return nextElement;
}

export interface SelectOption {
    key: number,
    label: string,
    value: string
};